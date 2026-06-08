<?php

namespace App\Support;

use App\Models\Athlete;
use App\Models\MatchGame;
use App\Models\MatchOfficial;
use App\Models\MatchParticipant;
use App\Models\Official;
use App\Models\Team;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ScheduleConflictDetector
{
    /**
     * @param  list<array{participant_type: string, participant_id: int}>  $participants
     * @param  list<array{official_id: int, role?: string}>  $officials
     * @return array{venue: list<string>, officials: list<string>, athletes: list<string>}
     */
    public function detect(
        ?Carbon $scheduledAt,
        int $durationMinutes,
        ?int $venueId,
        ?int $facilityId,
        array $participants,
        array $officials,
        ?int $excludeMatchId = null,
    ): array {
        if ($scheduledAt === null) {
            return [
                'venue' => [],
                'officials' => [],
                'athletes' => [],
            ];
        }

        $endsAt = $scheduledAt->copy()->addMinutes($durationMinutes);

        return [
            'venue' => $this->venueConflicts($scheduledAt, $endsAt, $venueId, $facilityId, $excludeMatchId),
            'officials' => $this->officialConflicts($scheduledAt, $endsAt, $officials, $excludeMatchId),
            'athletes' => $this->athleteConflicts($scheduledAt, $endsAt, $participants, $excludeMatchId),
        ];
    }

    /**
     * @return list<string>
     */
    private function venueConflicts(
        Carbon $startsAt,
        Carbon $endsAt,
        ?int $venueId,
        ?int $facilityId,
        ?int $excludeMatchId,
    ): array {
        if ($venueId === null) {
            return [];
        }

        $conflicts = MatchGame::query()
            ->whereNotNull('scheduled_at')
            ->where('venue_id', $venueId)
            ->when($facilityId !== null, fn ($query) => $query->where('facility_id', $facilityId))
            ->when($excludeMatchId !== null, fn ($query) => $query->where('id', '!=', $excludeMatchId))
            ->get()
            ->filter(fn (MatchGame $match) => $this->overlaps(
                $startsAt,
                $endsAt,
                $match->scheduled_at,
                $match->endsAt(),
            ));

        return $conflicts
            ->map(fn (MatchGame $match) => sprintf(
                'Venue conflict with match #%d (%s)',
                $match->id,
                $match->scheduled_at?->toDateTimeString() ?? 'unscheduled',
            ))
            ->values()
            ->all();
    }

    /**
     * @param  list<array{official_id: int, role?: string}>  $officials
     * @return list<string>
     */
    private function officialConflicts(
        Carbon $startsAt,
        Carbon $endsAt,
        array $officials,
        ?int $excludeMatchId,
    ): array {
        $messages = [];

        foreach ($officials as $officialData) {
            $officialId = (int) $officialData['official_id'];

            $matchIds = MatchOfficial::query()
                ->where('official_id', $officialId)
                ->when($excludeMatchId !== null, fn ($query) => $query->where('match_id', '!=', $excludeMatchId))
                ->pluck('match_id');

            if ($matchIds->isEmpty()) {
                continue;
            }

            $conflicts = MatchGame::query()
                ->whereIn('id', $matchIds)
                ->whereNotNull('scheduled_at')
                ->get()
                ->filter(fn (MatchGame $match) => $this->overlaps(
                    $startsAt,
                    $endsAt,
                    $match->scheduled_at,
                    $match->endsAt(),
                ));

            $officialName = Official::query()->find($officialId)?->name ?? "Official #{$officialId}";

            foreach ($conflicts as $match) {
                $messages[] = sprintf(
                    '%s is already assigned to match #%d at %s',
                    $officialName,
                    $match->id,
                    $match->scheduled_at?->toDateTimeString(),
                );
            }
        }

        return $messages;
    }

    /**
     * @param  list<array{participant_type: string, participant_id: int}>  $participants
     * @return list<string>
     */
    private function athleteConflicts(
        Carbon $startsAt,
        Carbon $endsAt,
        array $participants,
        ?int $excludeMatchId,
    ): array {
        $athleteIds = $this->resolveAthleteIds($participants);

        if ($athleteIds->isEmpty()) {
            return [];
        }

        $messages = [];

        foreach ($athleteIds as $athleteId) {
            $participantMatchIds = MatchParticipant::query()
                ->where(function ($query) use ($athleteId) {
                    $query
                        ->where(function ($athleteQuery) use ($athleteId) {
                            $athleteQuery
                                ->where('participant_type', Athlete::class)
                                ->where('participant_id', $athleteId);
                        })
                        ->orWhere(function ($teamQuery) use ($athleteId) {
                            $teamIds = Team::query()
                                ->whereHas('athletes', fn ($athletes) => $athletes->where('athletes.id', $athleteId))
                                ->pluck('id');

                            if ($teamIds->isNotEmpty()) {
                                $teamQuery
                                    ->where('participant_type', Team::class)
                                    ->whereIn('participant_id', $teamIds);
                            }
                        });
                })
                ->when($excludeMatchId !== null, fn ($query) => $query->where('match_id', '!=', $excludeMatchId))
                ->pluck('match_id');

            if ($participantMatchIds->isEmpty()) {
                continue;
            }

            $conflicts = MatchGame::query()
                ->whereIn('id', $participantMatchIds)
                ->whereNotNull('scheduled_at')
                ->get()
                ->filter(fn (MatchGame $match) => $this->overlaps(
                    $startsAt,
                    $endsAt,
                    $match->scheduled_at,
                    $match->endsAt(),
                ));

            $athleteName = Athlete::query()->find($athleteId)?->name ?? "Athlete #{$athleteId}";

            foreach ($conflicts as $match) {
                $messages[] = sprintf(
                    '%s has another match at %s (match #%d)',
                    $athleteName,
                    $match->scheduled_at?->toDateTimeString(),
                    $match->id,
                );
            }
        }

        return $messages;
    }

    /**
     * @param  list<array{participant_type: string, participant_id: int}>  $participants
     * @return Collection<int, int>
     */
    private function resolveAthleteIds(array $participants): Collection
    {
        $athleteIds = collect();

        foreach ($participants as $participant) {
            if ($participant['participant_type'] === Athlete::class) {
                $athleteIds->push((int) $participant['participant_id']);
            }

            if ($participant['participant_type'] === Team::class) {
                $teamAthleteIds = Team::query()
                    ->with('athletes:id')
                    ->find($participant['participant_id'])
                    ?->athletes
                    ->pluck('id') ?? collect();

                $athleteIds = $athleteIds->merge($teamAthleteIds);
            }
        }

        return $athleteIds->unique()->values();
    }

    private function overlaps(
        Carbon $startsAt,
        Carbon $endsAt,
        ?Carbon $otherStartsAt,
        ?Carbon $otherEndsAt,
    ): bool {
        if ($otherStartsAt === null || $otherEndsAt === null) {
            return false;
        }

        return $startsAt->lt($otherEndsAt) && $endsAt->gt($otherStartsAt);
    }
}