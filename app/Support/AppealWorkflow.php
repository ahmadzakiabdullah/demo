<?php

namespace App\Support;

use App\Enums\AppealStatus;
use App\Enums\MatchParticipantSide;
use App\Enums\ResultStatus;
use App\Models\MatchGame;
use App\Models\MatchParticipant;
use App\Models\Result;
use App\Models\ResultAppeal;
use App\Models\User;
use Illuminate\Support\Carbon;
use RuntimeException;

class AppealWorkflow
{
    public function __construct(
        private readonly RankingCalculator $rankingCalculator,
        private readonly MedalAllocator $medalAllocator,
    ) {}

    /**
     * @param  array{reason: string, proposed_home_score?: int|null, proposed_away_score?: int|null}  $payload
     */
    public function submit(Result $result, array $payload, User $user): ResultAppeal
    {
        if (! in_array($result->status, [ResultStatus::Confirmed, ResultStatus::Published], true)) {
            throw new RuntimeException('Appeals can only be filed against confirmed or published results.');
        }

        if ($result->openAppeal() !== null) {
            throw new RuntimeException('An open appeal already exists for this result.');
        }

        $organization = $result->match?->competition()?->organization;

        if ($organization === null) {
            throw new RuntimeException('Result is not linked to an organization.');
        }

        return ResultAppeal::query()->create([
            'organization_id' => $organization->id,
            'result_id' => $result->id,
            'submitted_by' => $user->id,
            'reason' => $payload['reason'],
            'status' => AppealStatus::Submitted,
            'proposed_home_score' => $payload['proposed_home_score'] ?? null,
            'proposed_away_score' => $payload['proposed_away_score'] ?? null,
        ]);
    }

    /**
     * @param  array{status: string, resolution_notes?: string|null, proposed_home_score?: int|null, proposed_away_score?: int|null}  $payload
     */
    public function resolve(ResultAppeal $appeal, array $payload, User $user): ResultAppeal
    {
        if (! $appeal->status->isOpen()) {
            throw new RuntimeException('This appeal has already been resolved.');
        }

        $status = AppealStatus::from($payload['status']);

        if ($status === AppealStatus::Submitted) {
            throw new RuntimeException('Invalid appeal resolution status.');
        }

        if ($status === AppealStatus::UnderReview) {
            $appeal->update(['status' => AppealStatus::UnderReview]);

            return $appeal->fresh();
        }

        $appeal->update([
            'status' => $status,
            'reviewed_by' => $user->id,
            'reviewed_at' => Carbon::now(),
            'resolution_notes' => $payload['resolution_notes'] ?? null,
        ]);

        if ($status === AppealStatus::Overturned) {
            $this->applyOverturn($appeal, $payload);
        }

        return $appeal->fresh();
    }

    /**
     * @param  array{proposed_home_score?: int|null, proposed_away_score?: int|null}  $payload
     */
    private function applyOverturn(ResultAppeal $appeal, array $payload): void
    {
        $result = $appeal->result()->with(['match.participants', 'match.advancesTo.participants'])->first();

        if ($result === null) {
            return;
        }

        $homeScore = $payload['proposed_home_score'] ?? $appeal->proposed_home_score;
        $awayScore = $payload['proposed_away_score'] ?? $appeal->proposed_away_score;

        if ($homeScore === null || $awayScore === null) {
            throw new RuntimeException('Proposed scores are required when overturning a result.');
        }

        $previousWinnerSide = $result->winnerSide();

        $winnerSide = match (true) {
            $homeScore > $awayScore => MatchParticipantSide::Home->value,
            $awayScore > $homeScore => MatchParticipantSide::Away->value,
            default => null,
        };

        $result->update([
            'data' => [
                'home_score' => (int) $homeScore,
                'away_score' => (int) $awayScore,
                'winner_side' => $winnerSide,
            ],
            'status' => ResultStatus::Pending,
            'confirmed_by' => null,
            'confirmed_at' => null,
            'published_at' => null,
        ]);

        $match = $result->match;

        if ($match !== null && $previousWinnerSide !== null && $previousWinnerSide !== $winnerSide) {
            $this->reverseKnockoutAdvance($match, $previousWinnerSide);
        }

        $competition = $match?->competition();

        if ($competition !== null) {
            $this->rankingCalculator->recalculate($competition);
            $this->medalAllocator->allocate($competition);
        }
    }

    private function reverseKnockoutAdvance(MatchGame $match, string $previousWinnerSide): void
    {
        if ($match->winner_advances_to_match_id === null) {
            return;
        }

        $nextMatch = $match->advancesTo;

        if ($nextMatch === null) {
            return;
        }

        $previousWinner = $match->participants->firstWhere('side', $previousWinnerSide);

        if ($previousWinner === null) {
            return;
        }

        $nextMatch->participants()
            ->where('side', $match->winner_advances_side)
            ->where('participant_type', $previousWinner->participant_type)
            ->where('participant_id', $previousWinner->participant_id)
            ->delete();
    }
}