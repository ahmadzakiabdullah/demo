<?php

namespace App\Support;

use App\Enums\ResultStatus;
use App\Models\Competition;
use App\Models\CompetitionGroup;
use App\Models\Fixture;
use App\Models\MatchGame;
use App\Models\MatchParticipant;

class GroupStandingsCalculator
{
    /**
     * @return list<array{group: CompetitionGroup, standings: list<array{type: string, id: int, name: string, points: int, played: int, goal_difference: int}>}>
     */
    public function calculate(Competition $competition): array
    {
        $rules = RankingRules::forCompetition($competition);
        $groups = $competition->groups()->orderBy('sort_order')->get();
        $output = [];

        foreach ($groups as $group) {
            $stats = [];
            $fixtures = Fixture::query()
                ->where('competition_id', $competition->id)
                ->where('group_id', $group->id)
                ->with(['matches.participants', 'matches.result'])
                ->get();

            foreach ($fixtures as $fixture) {
                foreach ($fixture->matches as $match) {
                    $this->accumulateMatch($match, $stats, $rules);
                }
            }

            uasort($stats, function (array $a, array $b) use ($rules) {
                if ($a['points'] !== $b['points']) {
                    return $b['points'] <=> $a['points'];
                }

                foreach ($rules['tiebreakers'] as $tiebreaker) {
                    $cmp = match ($tiebreaker) {
                        'goal_difference' => ($b['scored_for'] - $b['scored_against']) <=> ($a['scored_for'] - $a['scored_against']),
                        'goals_for' => $b['scored_for'] <=> $a['scored_for'],
                        default => 0,
                    };

                    if ($cmp !== 0) {
                        return $cmp;
                    }
                }

                return 0;
            });

            $standings = array_values(array_map(fn (array $entry) => [
                'type' => $entry['type'],
                'id' => $entry['id'],
                'name' => $entry['name'],
                'points' => $entry['points'],
                'played' => $entry['played'],
                'goal_difference' => $entry['scored_for'] - $entry['scored_against'],
            ], $stats));

            $output[] = [
                'group' => $group,
                'standings' => $standings,
            ];
        }

        return $output;
    }

    /**
     * @param  array<string, array{type: string, id: int, name: string, points: int, played: int, won: int, drawn: int, lost: int, scored_for: int, scored_against: int}>  $stats
     * @param  array{points_win: int, points_draw: int, points_loss: int}  $rules
     */
    private function accumulateMatch(MatchGame $match, array &$stats, array $rules): void
    {
        $result = $match->result;

        if ($result === null || ! in_array($result->status, [ResultStatus::Confirmed, ResultStatus::Published], true)) {
            return;
        }

        $home = $match->participants->firstWhere('side', 'home');
        $away = $match->participants->firstWhere('side', 'away');

        if ($home === null || $away === null) {
            return;
        }

        $homeKey = $this->participantKey($home);
        $awayKey = $this->participantKey($away);

        $stats[$homeKey] ??= $this->emptyStats($home);
        $stats[$awayKey] ??= $this->emptyStats($away);

        $homeScore = (int) ($result->data['home_score'] ?? 0);
        $awayScore = (int) ($result->data['away_score'] ?? 0);

        $stats[$homeKey]['played']++;
        $stats[$awayKey]['played']++;
        $stats[$homeKey]['scored_for'] += $homeScore;
        $stats[$homeKey]['scored_against'] += $awayScore;
        $stats[$awayKey]['scored_for'] += $awayScore;
        $stats[$awayKey]['scored_against'] += $homeScore;

        if ($homeScore > $awayScore) {
            $stats[$homeKey]['won']++;
            $stats[$homeKey]['points'] += $rules['points_win'];
            $stats[$awayKey]['lost']++;
            $stats[$awayKey]['points'] += $rules['points_loss'];
        } elseif ($homeScore < $awayScore) {
            $stats[$awayKey]['won']++;
            $stats[$awayKey]['points'] += $rules['points_win'];
            $stats[$homeKey]['lost']++;
            $stats[$homeKey]['points'] += $rules['points_loss'];
        } else {
            $stats[$homeKey]['drawn']++;
            $stats[$awayKey]['drawn']++;
            $stats[$homeKey]['points'] += $rules['points_draw'];
            $stats[$awayKey]['points'] += $rules['points_draw'];
        }
    }

    /**
     * @return array{type: string, id: int, name: string, points: int, played: int, won: int, drawn: int, lost: int, scored_for: int, scored_against: int}
     */
    private function emptyStats(MatchParticipant $participant): array
    {
        return [
            'type' => $participant->participant_type,
            'id' => $participant->participant_id,
            'name' => $participant->participant?->name ?? 'Unknown',
            'points' => 0,
            'played' => 0,
            'won' => 0,
            'drawn' => 0,
            'lost' => 0,
            'scored_for' => 0,
            'scored_against' => 0,
        ];
    }

    private function participantKey(MatchParticipant $participant): string
    {
        return "{$participant->participant_type}:{$participant->participant_id}";
    }
}