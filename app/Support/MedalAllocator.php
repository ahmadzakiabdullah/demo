<?php

namespace App\Support;

use App\Enums\MedalType;
use App\Enums\ResultStatus;
use App\Models\Competition;
use App\Models\MatchGame;
use App\Models\MatchParticipant;
use App\Models\Medal;
use App\Models\Ranking;

class MedalAllocator
{
    public function allocate(Competition $competition): void
    {
        $format = $competition->format?->slug;

        $competition->medals()->delete();

        if (in_array($format, ['round_robin', 'league', 'group_stage'], true)) {
            $this->allocateFromStandings($competition);

            return;
        }

        if (in_array($format, ['knockout', 'double_elimination'], true)) {
            $this->allocateFromKnockout($competition, $format === 'double_elimination' ? 'Grand Final' : 'Final');
        }

        if ($format === 'ladder') {
            $this->allocateFromLadder($competition);
        }
    }

    private function allocateFromStandings(Competition $competition): void
    {
        $top = $competition->rankings()->orderBy('position')->limit(3)->get();

        $medalTypes = [MedalType::Gold, MedalType::Silver, MedalType::Bronze];

        foreach ($top as $index => $ranking) {
            if (! isset($medalTypes[$index])) {
                break;
            }

            $this->storeMedal($competition, $ranking->rankable_type, $ranking->rankable_id, $medalTypes[$index]);
        }
    }

    private function allocateFromLadder(Competition $competition): void
    {
        $top = $competition->competitionParticipants()
            ->orderBy('ladder_rank')
            ->limit(3)
            ->get();

        $medalTypes = [MedalType::Gold, MedalType::Silver, MedalType::Bronze];

        foreach ($top as $index => $entry) {
            if (! isset($medalTypes[$index])) {
                break;
            }

            $this->storeMedal($competition, $entry->participant_type, $entry->participant_id, $medalTypes[$index]);
        }
    }

    private function allocateFromKnockout(Competition $competition, string $finalRound = 'Final'): void
    {
        $finalFixture = $competition->fixtures()
            ->where('round', $finalRound)
            ->orderByDesc('sort_order')
            ->first();

        if ($finalFixture === null) {
            return;
        }

        $finalMatch = $finalFixture->matches()->with(['result', 'participants'])->first();

        if ($finalMatch?->result === null || ! in_array($finalMatch->result->status, [ResultStatus::Confirmed, ResultStatus::Published], true)) {
            return;
        }

        $winnerSide = $finalMatch->result->winnerSide();
        $loserSide = $winnerSide === 'home' ? 'away' : 'home';

        $winner = $finalMatch->participants->firstWhere('side', $winnerSide);
        $runnerUp = $finalMatch->participants->firstWhere('side', $loserSide);

        if ($winner !== null) {
            $this->storeMedal($competition, $winner->participant_type, $winner->participant_id, MedalType::Gold);
        }

        if ($runnerUp !== null) {
            $this->storeMedal($competition, $runnerUp->participant_type, $runnerUp->participant_id, MedalType::Silver);
        }

        $semiFixtures = $competition->fixtures()->where('round', 'Semi-final')->get();

        foreach ($semiFixtures as $fixture) {
            foreach ($fixture->matches as $match) {
                $this->awardBronzeFromSemi($competition, $match, $finalMatch);
            }
        }
    }

    private function awardBronzeFromSemi(Competition $competition, MatchGame $semiMatch, MatchGame $finalMatch): void
    {
        $semiMatch->loadMissing(['result', 'participants']);

        if ($semiMatch->result === null || ! in_array($semiMatch->result->status, [ResultStatus::Confirmed, ResultStatus::Published], true)) {
            return;
        }

        $winnerSide = $semiMatch->result->winnerSide();
        $loserSide = $winnerSide === 'home' ? 'away' : 'home';
        $loser = $semiMatch->participants->firstWhere('side', $loserSide);

        if ($loser === null) {
            return;
        }

        $finalParticipantIds = $finalMatch->participants->pluck('participant_id');

        if ($finalParticipantIds->contains($loser->participant_id)) {
            return;
        }

        $this->storeMedal($competition, $loser->participant_type, $loser->participant_id, MedalType::Bronze);
    }

    private function storeMedal(Competition $competition, string $type, int $id, MedalType $medal): void
    {
        $eventParticipantId = null;

        if ($type === \App\Models\Team::class) {
            $team = \App\Models\Team::find($id);
            $eventParticipantId = $team?->event_participant_id;
        } elseif ($type === \App\Models\Athlete::class) {
            $athlete = \App\Models\Athlete::find($id);
            $eventParticipantId = $athlete?->event_participant_id;
        }

        Medal::query()->updateOrCreate(
            [
                'competition_id' => $competition->id,
                'medalable_type' => $type,
                'medalable_id' => $id,
                'type' => $medal->value,
            ],
            [
                'event_id' => $competition->event_id,
                'sport_id' => $competition->sport_id,
                'event_participant_id' => $eventParticipantId,
            ],
        );
    }
}