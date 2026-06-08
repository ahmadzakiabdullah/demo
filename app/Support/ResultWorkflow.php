<?php

namespace App\Support;

use App\Enums\BracketLane;
use App\Enums\MatchParticipantSide;
use App\Enums\MatchStatus;
use App\Enums\ResultStatus;
use App\Events\ResultScoreUpdated;
use App\Models\CompetitionParticipant;
use App\Models\MatchGame;
use App\Models\MatchParticipant;
use App\Models\Result;
use App\Models\User;
use Illuminate\Support\Carbon;

class ResultWorkflow
{
    public function __construct(
        private readonly RankingCalculator $rankingCalculator,
        private readonly MedalAllocator $medalAllocator,
    ) {}

    /**
     * @param  array{home_score: int|float, away_score: int|float, notes?: string|null}  $payload
     */
    public function record(MatchGame $match, array $payload, User $user): Result
    {
        $sport = $match->competition()?->sport;
        $schema = ScoreSchema::forSport($sport);
        $normalized = ScoreSchema::normalizeResult($schema, $payload);

        $result = Result::query()->updateOrCreate(
            ['match_id' => $match->id],
            [
                'entered_by' => $user->id,
                'data' => [
                    'home_score' => $normalized['home_score'],
                    'away_score' => $normalized['away_score'],
                    'winner_side' => $normalized['winner_side'],
                    'schema_type' => $schema['type'],
                ],
                'status' => ResultStatus::Pending,
                'confirmed_by' => null,
                'confirmed_at' => null,
                'published_at' => null,
                'notes' => $payload['notes'] ?? null,
            ],
        );

        $match->update(['status' => MatchStatus::Completed]);

        $this->broadcastUpdate($result);

        return $result;
    }

    public function advanceStatus(Result $result, string $status, User $user): Result
    {
        $statusEnum = ResultStatus::from($status);

        if ($statusEnum === ResultStatus::Confirmed) {
            $result->update([
                'status' => ResultStatus::Confirmed,
                'confirmed_by' => $user->id,
                'confirmed_at' => Carbon::now(),
            ]);

            $this->afterConfirmation($result);
        }

        if ($statusEnum === ResultStatus::Published) {
            if ($result->status !== ResultStatus::Confirmed) {
                $result->update([
                    'status' => ResultStatus::Confirmed,
                    'confirmed_by' => $user->id,
                    'confirmed_at' => Carbon::now(),
                ]);
                $this->afterConfirmation($result);
            }

            $result->update([
                'status' => ResultStatus::Published,
                'published_at' => Carbon::now(),
            ]);
        }

        $result = $result->fresh();
        $this->broadcastUpdate($result);

        return $result;
    }

    private function afterConfirmation(Result $result): void
    {
        $match = $result->match()->with([
            'participants',
            'advancesTo.participants',
            'loserAdvancesTo.participants',
        ])->first();

        if ($match === null) {
            return;
        }

        $this->advanceKnockoutWinner($match, $result);
        $this->advanceKnockoutLoser($match, $result);
        $this->updateSwissPoints($match, $result);
        $this->updateLadderRanks($match, $result);

        $competition = $match->competition();

        if ($competition === null) {
            return;
        }

        $this->rankingCalculator->recalculate($competition);
        $this->medalAllocator->allocate($competition);
    }

    private function advanceKnockoutWinner(MatchGame $match, Result $result): void
    {
        if ($match->winner_advances_to_match_id === null || $result->winnerSide() === null) {
            return;
        }

        $nextMatch = $match->advancesTo;

        if ($nextMatch === null) {
            return;
        }

        $winner = $match->participants->firstWhere('side', $result->winnerSide());

        if ($winner === null) {
            return;
        }

        $nextMatch->participants()
            ->where('side', $match->winner_advances_side)
            ->delete();

        MatchParticipant::query()->create([
            'match_id' => $nextMatch->id,
            'participant_type' => $winner->participant_type,
            'participant_id' => $winner->participant_id,
            'side' => $match->winner_advances_side,
        ]);
    }

    private function advanceKnockoutLoser(MatchGame $match, Result $result): void
    {
        if ($match->loser_advances_to_match_id === null || $result->winnerSide() === null) {
            return;
        }

        $loserSide = $result->winnerSide() === MatchParticipantSide::Home->value ? 'away' : 'home';
        $loser = $match->participants->firstWhere('side', $loserSide);
        $nextMatch = $match->loserAdvancesTo;

        if ($loser === null || $nextMatch === null) {
            return;
        }

        $nextMatch->participants()
            ->where('side', $match->loser_advances_side)
            ->delete();

        MatchParticipant::query()->create([
            'match_id' => $nextMatch->id,
            'participant_type' => $loser->participant_type,
            'participant_id' => $loser->participant_id,
            'side' => $match->loser_advances_side,
        ]);
    }

    private function updateSwissPoints(MatchGame $match, Result $result): void
    {
        if ($match->bracket_lane !== BracketLane::Swiss->value || $result->winnerSide() === null) {
            return;
        }

        $competition = $match->competition();

        if ($competition === null) {
            return;
        }

        foreach ($match->participants as $participant) {
            $entry = CompetitionParticipant::query()
                ->where('competition_id', $competition->id)
                ->where('participant_type', $participant->participant_type)
                ->where('participant_id', $participant->participant_id)
                ->first();

            if ($entry === null) {
                continue;
            }

            $won = $participant->side->value === $result->winnerSide();
            $entry->update([
                'swiss_points' => $entry->swiss_points + ($won ? 1 : 0),
            ]);
        }
    }

    private function updateLadderRanks(MatchGame $match, Result $result): void
    {
        if ($match->bracket_lane !== BracketLane::Ladder->value || $result->winnerSide() === null) {
            return;
        }

        $competition = $match->competition();

        if ($competition === null) {
            return;
        }

        $home = $match->participants->firstWhere('side', 'home');
        $away = $match->participants->firstWhere('side', 'away');

        if ($home === null || $away === null) {
            return;
        }

        $homeEntry = CompetitionParticipant::query()
            ->where('competition_id', $competition->id)
            ->where('participant_type', $home->participant_type)
            ->where('participant_id', $home->participant_id)
            ->first();
        $awayEntry = CompetitionParticipant::query()
            ->where('competition_id', $competition->id)
            ->where('participant_type', $away->participant_type)
            ->where('participant_id', $away->participant_id)
            ->first();

        if ($homeEntry === null || $awayEntry === null) {
            return;
        }

        if ($result->winnerSide() === 'home' && $homeEntry->ladder_rank > $awayEntry->ladder_rank) {
            $homeEntry->update(['ladder_rank' => $awayEntry->ladder_rank]);
            $awayEntry->update(['ladder_rank' => $awayEntry->ladder_rank + 1]);
        }

        if ($result->winnerSide() === 'away' && $awayEntry->ladder_rank > $homeEntry->ladder_rank) {
            $awayEntry->update(['ladder_rank' => $homeEntry->ladder_rank]);
            $homeEntry->update(['ladder_rank' => $homeEntry->ladder_rank + 1]);
        }
    }

    private function broadcastUpdate(Result $result): void
    {
        $event = $result->match?->event();

        if ($event === null) {
            return;
        }

        event(new ResultScoreUpdated($result, $event->id));
    }
}