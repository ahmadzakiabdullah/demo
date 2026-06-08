<?php

namespace App\Support;

use App\Enums\BracketLane;
use App\Enums\MatchParticipantSide;
use App\Enums\MatchStatus;
use App\Models\Competition;
use App\Models\CompetitionParticipant;
use App\Models\Fixture;
use App\Models\MatchGame;
use App\Models\MatchParticipant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DrawGenerator
{
    public function __construct(
        private readonly ParticipantResolver $participantResolver,
        private readonly DoubleEliminationDraw $doubleEliminationDraw,
        private readonly GroupStandingsCalculator $groupStandingsCalculator,
    ) {}

    /**
     * @return array{participants: int, fixtures: int, matches: int}
     */
    public function generate(Competition $competition): array
    {
        $format = $competition->format?->slug;

        return match ($format) {
            'round_robin', 'league' => $this->generateRoundRobin($competition),
            'knockout' => $this->generateKnockout($competition),
            'double_elimination' => $this->generateDoubleElimination($competition),
            'group_stage' => $this->generateGroupStage($competition),
            'swiss' => $this->generateSwiss($competition),
            'ladder' => $this->generateLadder($competition),
            default => throw new RuntimeException("Draw generation is not supported for format [{$format}]."),
        };
    }

    /**
     * @return array{participants: int, fixtures: int, matches: int}
     */
    public function generateKnockoutPhase(Competition $competition): array
    {
        if ($competition->format?->slug !== 'group_stage') {
            throw new RuntimeException('Knockout phase is only available for group stage competitions.');
        }

        $advancePerGroup = (int) ($competition->settings['group_advance_count'] ?? 2);
        $groupStandings = $this->groupStandingsCalculator->calculate($competition);

        if ($groupStandings === []) {
            throw new RuntimeException('Create groups and complete group matches before generating the knockout phase.');
        }

        /** @var Collection<int, array{type: class-string, id: int, name: string, seed: int}> $advancers */
        $advancers = collect();
        $seed = 1;

        foreach ($groupStandings as $groupResult) {
            foreach (array_slice($groupResult['standings'], 0, $advancePerGroup) as $standing) {
                $advancers->push([
                    'type' => $standing['type'],
                    'id' => $standing['id'],
                    'name' => $standing['name'],
                    'seed' => $seed++,
                ]);
            }
        }

        if ($advancers->count() < 2) {
            throw new RuntimeException('Not enough qualified participants for a knockout phase.');
        }

        return DB::transaction(function () use ($competition, $advancers) {
            $existingKnockout = $competition->fixtures()
                ->where('round', 'Knockout')
                ->exists();

            if ($existingKnockout) {
                throw new RuntimeException('Knockout phase has already been generated.');
            }

            return $this->buildKnockoutBracket($competition, $advancers, 'Knockout', BracketLane::Knockout, 100);
        });
    }

    /**
     * @return array{participants: int, fixtures: int, matches: int}
     */
    private function generateRoundRobin(Competition $competition): array
    {
        $participants = $this->participantResolver->resolve($competition);

        if ($participants->count() < 2) {
            throw new RuntimeException('At least two approved participants are required for a draw.');
        }

        return DB::transaction(function () use ($competition, $participants) {
            $this->clearExistingDraw($competition);

            $fixture = Fixture::query()->create([
                'competition_id' => $competition->id,
                'name' => 'Round Robin',
                'round' => 'League',
                'sort_order' => 0,
            ]);

            $matchCount = 0;
            $items = $participants->values();

            for ($i = 0; $i < $items->count(); $i++) {
                for ($j = $i + 1; $j < $items->count(); $j++) {
                    $match = MatchGame::query()->create([
                        'fixture_id' => $fixture->id,
                        'status' => MatchStatus::Scheduled,
                    ]);

                    $this->attachParticipants($match, $items[$i], $items[$j]);
                    $matchCount++;
                }
            }

            return [
                'participants' => $items->count(),
                'fixtures' => 1,
                'matches' => $matchCount,
            ];
        });
    }

    /**
     * @return array{participants: int, fixtures: int, matches: int}
     */
    private function generateKnockout(Competition $competition): array
    {
        $participants = $this->participantResolver->resolve($competition);

        if ($participants->count() < 2) {
            throw new RuntimeException('At least two approved participants are required for a draw.');
        }

        return DB::transaction(function () use ($competition, $participants) {
            $this->clearExistingDraw($competition);

            return $this->buildKnockoutBracket($competition, $participants, 'Knockout', BracketLane::Knockout, 0);
        });
    }

    /**
     * @return array{participants: int, fixtures: int, matches: int}
     */
    private function generateDoubleElimination(Competition $competition): array
    {
        $participants = $this->participantResolver->resolve($competition);

        return $this->doubleEliminationDraw->generate($competition, $participants);
    }

    /**
     * @return array{participants: int, fixtures: int, matches: int}
     */
    private function generateGroupStage(Competition $competition): array
    {
        $groups = $competition->groups()->orderBy('sort_order')->get();

        if ($groups->isEmpty()) {
            throw new RuntimeException('Create at least one group before generating a group stage draw.');
        }

        $participants = $this->participantResolver->resolve($competition);

        if ($participants->count() < $groups->count() * 2) {
            throw new RuntimeException('Not enough participants for the configured groups.');
        }

        return DB::transaction(function () use ($competition, $groups, $participants) {
            $this->clearExistingDraw($competition);

            $chunks = $participants->values()->chunk((int) ceil($participants->count() / $groups->count()));
            $fixtureCount = 0;
            $matchCount = 0;

            foreach ($groups as $index => $group) {
                $groupParticipants = $chunks[$index] ?? collect();

                if ($groupParticipants->count() < 2) {
                    continue;
                }

                $fixture = Fixture::query()->create([
                    'competition_id' => $competition->id,
                    'group_id' => $group->id,
                    'name' => "{$group->name} — Round Robin",
                    'round' => 'Group Stage',
                    'sort_order' => $index,
                ]);
                $fixtureCount++;

                $items = $groupParticipants->values();

                for ($i = 0; $i < $items->count(); $i++) {
                    for ($j = $i + 1; $j < $items->count(); $j++) {
                        $match = MatchGame::query()->create([
                            'fixture_id' => $fixture->id,
                            'status' => MatchStatus::Scheduled,
                        ]);

                        $this->attachParticipants($match, $items[$i], $items[$j]);
                        $matchCount++;
                    }
                }
            }

            return [
                'participants' => $participants->count(),
                'fixtures' => $fixtureCount,
                'matches' => $matchCount,
            ];
        });
    }

    /**
     * @return array{participants: int, fixtures: int, matches: int}
     */
    private function generateSwiss(Competition $competition): array
    {
        $participants = $this->participantResolver->resolve($competition);

        if ($participants->count() < 4) {
            throw new RuntimeException('Swiss system requires at least four participants.');
        }

        $rounds = (int) ($competition->settings['swiss_rounds'] ?? (int) ceil(log($participants->count(), 2)));

        return DB::transaction(function () use ($competition, $participants, $rounds) {
            $this->clearExistingDraw($competition);

            $fixtureCount = 0;
            $matchCount = 0;

            for ($round = 1; $round <= $rounds; $round++) {
                $fixture = Fixture::query()->create([
                    'competition_id' => $competition->id,
                    'name' => "Swiss — Round {$round}",
                    'round' => "Swiss {$round}",
                    'sort_order' => $round - 1,
                ]);
                $fixtureCount++;

                $pairings = $this->swissPairings($competition, $participants, $round);

                foreach ($pairings as $pair) {
                    $match = MatchGame::query()->create([
                        'fixture_id' => $fixture->id,
                        'status' => MatchStatus::Scheduled,
                        'bracket_lane' => BracketLane::Swiss->value,
                    ]);

                    if ($round === 1) {
                        $this->attachParticipants($match, $pair[0], $pair[1]);
                    }

                    $matchCount++;
                }
            }

            return [
                'participants' => $participants->count(),
                'fixtures' => $fixtureCount,
                'matches' => $matchCount,
            ];
        });
    }

    /**
     * @return array{participants: int, fixtures: int, matches: int}
     */
    private function generateLadder(Competition $competition): array
    {
        $participants = $this->participantResolver->resolve($competition);

        if ($participants->count() < 2) {
            throw new RuntimeException('Ladder format requires at least two participants.');
        }

        return DB::transaction(function () use ($competition, $participants) {
            $this->clearExistingDraw($competition);

            $ordered = $participants->sortBy('seed')->values();
            $challenger = $ordered[1];
            $leader = $ordered[0];

            $fixture = Fixture::query()->create([
                'competition_id' => $competition->id,
                'name' => 'Ladder Challenge',
                'round' => 'Ladder',
                'sort_order' => 0,
            ]);

            $match = MatchGame::query()->create([
                'fixture_id' => $fixture->id,
                'status' => MatchStatus::Scheduled,
                'bracket_lane' => BracketLane::Ladder->value,
            ]);

            $this->attachParticipants($match, $challenger, $leader);

            return [
                'participants' => $ordered->count(),
                'fixtures' => 1,
                'matches' => 1,
            ];
        });
    }

    /**
     * @param  Collection<int, array{type: class-string, id: int, name: string, seed: int}>  $participants
     * @return array{participants: int, fixtures: int, matches: int}
     */
    private function buildKnockoutBracket(
        Competition $competition,
        Collection $participants,
        string $roundLabel,
        BracketLane $lane,
        int $sortOffset,
    ): array {
        $bracketSize = (int) pow(2, (int) ceil(log($participants->count(), 2)));
        $roundCount = (int) log($bracketSize, 2);
        $roundNames = $this->knockoutRoundNames($roundCount, $bracketSize);

        /** @var list<list<MatchGame>> $roundMatches */
        $roundMatches = [];
        $fixtureCount = 0;
        $matchCount = 0;

        for ($round = 0; $round < $roundCount; $round++) {
            $matchesInRound = $bracketSize / (2 ** ($round + 1));
            $fixture = Fixture::query()->create([
                'competition_id' => $competition->id,
                'name' => "{$roundLabel} — {$roundNames[$round]}",
                'round' => $round === $roundCount - 1 && $roundLabel === 'Knockout' ? 'Knockout' : $roundNames[$round],
                'sort_order' => $sortOffset + $round,
            ]);
            $fixtureCount++;

            $currentRoundMatches = [];

            for ($m = 0; $m < $matchesInRound; $m++) {
                $match = MatchGame::query()->create([
                    'fixture_id' => $fixture->id,
                    'status' => MatchStatus::Scheduled,
                    'bracket_lane' => $lane->value,
                ]);
                $currentRoundMatches[] = $match;
                $matchCount++;
            }

            if ($round > 0) {
                foreach ($roundMatches[$round - 1] as $index => $previousMatch) {
                    $nextMatch = $currentRoundMatches[(int) floor($index / 2)];
                    $previousMatch->update([
                        'winner_advances_to_match_id' => $nextMatch->id,
                        'winner_advances_side' => $index % 2 === 0
                            ? MatchParticipantSide::Home->value
                            : MatchParticipantSide::Away->value,
                    ]);
                }
            }

            $roundMatches[$round] = $currentRoundMatches;
        }

        $seeded = $participants->values();
        $firstRound = $roundMatches[0];

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

        return [
            'participants' => $seeded->count(),
            'fixtures' => $fixtureCount,
            'matches' => $matchCount,
        ];
    }

    /**
     * @param  Collection<int, array{type: class-string, id: int, name: string, seed: int}>  $participants
     * @return list<array{0: array{type: class-string, id: int, name: string, seed: int}, 1: array{type: class-string, id: int, name: string, seed: int}}>
     */
    private function swissPairings(Competition $competition, Collection $participants, int $round): array
    {
        $ordered = $participants->sortBy('seed')->values();
        $half = (int) ceil($ordered->count() / 2);
        $pairings = [];

        if ($round === 1) {
            for ($i = 0; $i < $half; $i++) {
                $home = $ordered[$i];
                $away = $ordered[$i + $half] ?? null;

                if ($away !== null) {
                    $pairings[] = [$home, $away];
                }
            }

            return $pairings;
        }

        $sorted = CompetitionParticipant::query()
            ->where('competition_id', $competition->id)
            ->orderByDesc('swiss_points')
            ->orderBy('seed')
            ->get();

        $pool = $sorted->values();
        $used = [];

        for ($i = 0; $i < $pool->count(); $i++) {
            if (isset($used[$i])) {
                continue;
            }

            for ($j = $i + 1; $j < $pool->count(); $j++) {
                if (isset($used[$j])) {
                    continue;
                }

                $pairings[] = [
                    [
                        'type' => $pool[$i]->participant_type,
                        'id' => $pool[$i]->participant_id,
                        'name' => '',
                        'seed' => $pool[$i]->seed,
                    ],
                    [
                        'type' => $pool[$j]->participant_type,
                        'id' => $pool[$j]->participant_id,
                        'name' => '',
                        'seed' => $pool[$j]->seed,
                    ],
                ];
                $used[$i] = true;
                $used[$j] = true;
                break;
            }
        }

        return $pairings;
    }

    /**
     * @param  array{type: class-string, id: int, name: string}  $home
     * @param  array{type: class-string, id: int, name: string}  $away
     */
    private function attachParticipants(MatchGame $match, array $home, array $away): void
    {
        MatchParticipant::query()->create([
            'match_id' => $match->id,
            'participant_type' => $home['type'],
            'participant_id' => $home['id'],
            'side' => MatchParticipantSide::Home,
        ]);

        MatchParticipant::query()->create([
            'match_id' => $match->id,
            'participant_type' => $away['type'],
            'participant_id' => $away['id'],
            'side' => MatchParticipantSide::Away,
        ]);
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
        CompetitionParticipant::query()->where('competition_id', $competition->id)->delete();
    }

    /**
     * @return list<string>
     */
    private function knockoutRoundNames(int $roundCount, int $bracketSize): array
    {
        $labels = [
            2 => 'Final',
            4 => 'Semi-final',
            8 => 'Quarter-final',
            16 => 'Round of 16',
            32 => 'Round of 32',
        ];

        $names = [];

        for ($round = 0; $round < $roundCount; $round++) {
            $teamsInRound = $bracketSize / (2 ** $round);
            $names[] = $labels[$teamsInRound] ?? "Round {$round}";
        }

        return $names;
    }
}