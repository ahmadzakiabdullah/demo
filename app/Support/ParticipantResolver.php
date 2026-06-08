<?php

namespace App\Support;

use App\Enums\RegistrationStatus;
use App\Enums\SeedingStrategy;
use App\Models\Athlete;
use App\Models\Competition;
use App\Models\CompetitionParticipant;
use App\Models\Team;
use Illuminate\Support\Collection;

class ParticipantResolver
{
    /**
     * @return Collection<int, array{type: class-string, id: int, name: string, seed: int}>
     */
    public function resolve(Competition $competition): Collection
    {
        $strategy = SeedingStrategy::tryFrom(
            $competition->settings['seeding'] ?? SeedingStrategy::Name->value,
        ) ?? SeedingStrategy::Name;

        $participants = $this->loadApprovedParticipants($competition);

        if ($participants->isEmpty()) {
            return collect();
        }

        $manualSeeds = $strategy === SeedingStrategy::Manual
            ? CompetitionParticipant::query()
                ->where('competition_id', $competition->id)
                ->get()
                ->keyBy(fn (CompetitionParticipant $entry) => "{$entry->participant_type}:{$entry->participant_id}")
            : null;

        $this->syncCompetitionParticipants($competition, $participants, $strategy, $manualSeeds);

        return CompetitionParticipant::query()
            ->where('competition_id', $competition->id)
            ->orderBy('seed')
            ->get()
            ->map(fn (CompetitionParticipant $entry) => [
                'type' => $entry->participant_type,
                'id' => $entry->participant_id,
                'name' => $this->participantName($entry->participant_type, $entry->participant_id),
                'seed' => $entry->seed,
            ]);
    }

    /**
     * @return Collection<int, array{type: class-string, id: int, name: string}>
     */
    private function loadApprovedParticipants(Competition $competition): Collection
    {
        $teams = Team::query()
            ->where('event_id', $competition->event_id)
            ->where('sport_id', $competition->sport_id)
            ->whereHas('registrations', fn ($query) => $query
                ->where('event_id', $competition->event_id)
                ->where('status', RegistrationStatus::Approved))
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($teams->isNotEmpty()) {
            return $teams->map(fn (Team $team) => [
                'type' => Team::class,
                'id' => $team->id,
                'name' => $team->name,
            ]);
        }

        return Athlete::query()
            ->where('organization_id', $competition->organization_id)
            ->whereHas('registrations', fn ($query) => $query
                ->where('event_id', $competition->event_id)
                ->where('sport_id', $competition->sport_id)
                ->where('status', RegistrationStatus::Approved))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Athlete $athlete) => [
                'type' => Athlete::class,
                'id' => $athlete->id,
                'name' => $athlete->name,
            ]);
    }

    /**
     * @param  Collection<int, array{type: class-string, id: int, name: string}>  $participants
     */
    private function syncCompetitionParticipants(
        Competition $competition,
        Collection $participants,
        SeedingStrategy $strategy,
        mixed $manualSeeds = null,
    ): void {
        $ordered = match ($strategy) {
            SeedingStrategy::Random => $participants->shuffle()->values(),
            SeedingStrategy::Manual => $this->applyManualSeeds($participants, $manualSeeds),
            default => $participants->values(),
        };

        CompetitionParticipant::query()->where('competition_id', $competition->id)->delete();

        foreach ($ordered as $index => $participant) {
            CompetitionParticipant::query()->create([
                'competition_id' => $competition->id,
                'participant_type' => $participant['type'],
                'participant_id' => $participant['id'],
                'seed' => $index + 1,
                'ladder_rank' => $index + 1,
            ]);
        }
    }

    /**
     * @param  Collection<int, array{type: class-string, id: int, name: string}>  $participants
     * @return Collection<int, array{type: class-string, id: int, name: string, seed?: int}>
     */
    private function applyManualSeeds(Collection $participants, mixed $manualSeeds): Collection
    {
        return $participants
            ->map(function (array $participant) use ($manualSeeds) {
                $key = "{$participant['type']}:{$participant['id']}";
                $participant['seed'] = $manualSeeds[$key]->seed ?? 999;

                return $participant;
            })
            ->sortBy('seed')
            ->values();
    }

    private function participantName(string $type, int $id): string
    {
        $model = $type::query()->find($id);

        return $model?->name ?? 'Unknown';
    }
}