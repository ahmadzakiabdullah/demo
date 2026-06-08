<?php

namespace App\Support;

use App\Enums\BracketLane;
use App\Enums\MatchParticipantSide;
use App\Enums\MatchStatus;
use App\Models\Competition;
use App\Models\Fixture;
use App\Models\MatchGame;
use App\Models\MatchParticipant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DoubleEliminationDraw
{
    /**
     * @param  Collection<int, array{type: class-string, id: int, name: string, seed: int}>  $participants
     * @return array{participants: int, fixtures: int, matches: int}
     */
    public function generate(Competition $competition, Collection $participants): array
    {
        if ($participants->count() < 4) {
            throw new \RuntimeException('Double elimination requires at least four participants.');
        }

        $bracketSize = (int) pow(2, (int) ceil(log($participants->count(), 2)));

        if ($bracketSize > 16) {
            throw new \RuntimeException('Double elimination supports up to sixteen participants.');
        }

        return DB::transaction(function () use ($competition, $participants, $bracketSize) {
            $this->clearExistingDraw($competition);

            $seeded = $participants->values();
            $winnersRounds = (int) log($bracketSize, 2);
            $winnersMatches = [];
            $fixtureCount = 0;
            $matchCount = 0;

            for ($round = 0; $round < $winnersRounds; $round++) {
                $matchesInRound = $bracketSize / (2 ** ($round + 1));
                $fixture = Fixture::query()->create([
                    'competition_id' => $competition->id,
                    'name' => 'Winners — '.$this->roundLabel($matchesInRound * 2),
                    'round' => 'Winners '.$this->roundLabel($matchesInRound * 2),
                    'sort_order' => $round,
                ]);
                $fixtureCount++;

                $roundMatches = [];

                for ($m = 0; $m < $matchesInRound; $m++) {
                    $match = MatchGame::query()->create([
                        'fixture_id' => $fixture->id,
                        'status' => MatchStatus::Scheduled,
                        'bracket_lane' => BracketLane::Winners->value,
                    ]);
                    $roundMatches[] = $match;
                    $matchCount++;
                }

                if ($round > 0) {
                    foreach ($winnersMatches[$round - 1] as $index => $previousMatch) {
                        $nextMatch = $roundMatches[(int) floor($index / 2)];
                        $previousMatch->update([
                            'winner_advances_to_match_id' => $nextMatch->id,
                            'winner_advances_side' => $index % 2 === 0
                                ? MatchParticipantSide::Home->value
                                : MatchParticipantSide::Away->value,
                        ]);
                    }
                }

                $winnersMatches[$round] = $roundMatches;
            }

            $firstRound = $winnersMatches[0];

            foreach ($firstRound as $index => $match) {
                $home = $seeded[$index * 2] ?? null;
                $away = $seeded[$index * 2 + 1] ?? null;

                if ($home !== null) {
                    MatchParticipant::query()->create([
                        'match_id' => $match->id,
                        'participant_type' => $home['type'],
                        'participant_id' => $home['id'],
                        'side' => MatchParticipantSide::Home,
                    ]);
                }

                if ($away !== null) {
                    MatchParticipant::query()->create([
                        'match_id' => $match->id,
                        'participant_type' => $away['type'],
                        'participant_id' => $away['id'],
                        'side' => MatchParticipantSide::Away,
                    ]);
                }
            }

            $losersRounds = max(1, ($winnersRounds * 2) - 1);
            $losersMatches = [];
            $losersOffset = $winnersRounds;

            for ($round = 0; $round < $losersRounds; $round++) {
                $matchesInRound = max(1, (int) ($bracketSize / (2 ** ($round + 2))));
                if ($round === $losersRounds - 1) {
                    $matchesInRound = 1;
                }

                $fixture = Fixture::query()->create([
                    'competition_id' => $competition->id,
                    'name' => 'Losers — Round '.($round + 1),
                    'round' => 'Losers '.($round + 1),
                    'sort_order' => $losersOffset + $round,
                ]);
                $fixtureCount++;

                $roundMatches = [];

                for ($m = 0; $m < $matchesInRound; $m++) {
                    $match = MatchGame::query()->create([
                        'fixture_id' => $fixture->id,
                        'status' => MatchStatus::Scheduled,
                        'bracket_lane' => BracketLane::Losers->value,
                    ]);
                    $roundMatches[] = $match;
                    $matchCount++;
                }

                if ($round > 0) {
                    foreach ($losersMatches[$round - 1] as $index => $previousMatch) {
                        if (! isset($roundMatches[(int) floor($index / 2)])) {
                            continue;
                        }

                        $nextMatch = $roundMatches[(int) floor($index / 2)];
                        $previousMatch->update([
                            'winner_advances_to_match_id' => $nextMatch->id,
                            'winner_advances_side' => $index % 2 === 0
                                ? MatchParticipantSide::Home->value
                                : MatchParticipantSide::Away->value,
                        ]);
                    }
                }

                $losersMatches[$round] = $roundMatches;
            }

            foreach ($firstRound as $index => $winnersMatch) {
                $target = $losersMatches[0][(int) floor($index / 2)] ?? null;

                if ($target === null) {
                    continue;
                }

                $winnersMatch->update([
                    'loser_advances_to_match_id' => $target->id,
                    'loser_advances_side' => $index % 2 === 0
                        ? MatchParticipantSide::Home->value
                        : MatchParticipantSide::Away->value,
                ]);
            }

            $wbFinal = $winnersMatches[$winnersRounds - 1][0] ?? null;
            $lbFinal = $losersMatches[$losersRounds - 1][0] ?? null;

            $grandFixture = Fixture::query()->create([
                'competition_id' => $competition->id,
                'name' => 'Grand Final',
                'round' => 'Grand Final',
                'sort_order' => $losersOffset + $losersRounds,
            ]);
            $fixtureCount++;

            $grandFinal = MatchGame::query()->create([
                'fixture_id' => $grandFixture->id,
                'status' => MatchStatus::Scheduled,
                'bracket_lane' => BracketLane::GrandFinal->value,
            ]);
            $matchCount++;

            if ($wbFinal !== null) {
                $wbFinal->update([
                    'winner_advances_to_match_id' => $grandFinal->id,
                    'winner_advances_side' => MatchParticipantSide::Home->value,
                    'loser_advances_to_match_id' => $lbFinal?->id,
                    'loser_advances_side' => MatchParticipantSide::Away->value,
                ]);
            }

            if ($lbFinal !== null) {
                $lbFinal->update([
                    'winner_advances_to_match_id' => $grandFinal->id,
                    'winner_advances_side' => MatchParticipantSide::Away->value,
                ]);
            }

            return [
                'participants' => $seeded->count(),
                'fixtures' => $fixtureCount,
                'matches' => $matchCount,
            ];
        });
    }

    private function roundLabel(int $teamsInRound): string
    {
        return match ($teamsInRound) {
            2 => 'Final',
            4 => 'Semi-final',
            8 => 'Quarter-final',
            16 => 'Round of 16',
            default => "Round of {$teamsInRound}",
        };
    }

    private function clearExistingDraw(Competition $competition): void
    {
        $competition->fixtures()->each(function (Fixture $fixture) {
            $fixture->matches()->each(fn (MatchGame $match) => $match->participants()->delete());
            $fixture->matches()->delete();
            $fixture->delete();
        });

        $competition->rankings()->delete();
        $competition->medals()->delete();
    }
}