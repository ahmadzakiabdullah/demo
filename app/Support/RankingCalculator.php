<?php

namespace App\Support;

use App\Enums\ResultStatus;
use App\Models\Competition;
use App\Models\MatchGame;
use App\Models\MatchParticipant;
use App\Models\Ranking;
use App\Models\Result;

class RankingCalculator
{
    public function recalculate(Competition $competition): void
    {
        $format = $competition->format?->slug;

        if (! in_array($format, ['round_robin', 'league', 'group_stage', 'swiss'], true)) {
            return;
        }

        $rules = RankingRules::forCompetition($competition);
        $stats = [];

        $matches = MatchGame::query()
            ->whereHas('fixture', fn ($query) => $query->where('competition_id', $competition->id))
            ->with(['participants', 'result'])
            ->get();

        foreach ($matches as $match) {
            $result = $match->result;

            if ($result === null || ! in_array($result->status, [ResultStatus::Confirmed, ResultStatus::Published], true)) {
                continue;
            }

            $home = $match->participants->firstWhere('side', 'home');
            $away = $match->participants->firstWhere('side', 'away');

            if ($home === null || $away === null) {
                continue;
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

        if ($format === 'swiss') {
            $this->applySwissStandings($competition, $stats);

            return;
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

        $competition->rankings()->delete();

        $position = 1;
        foreach ($stats as $entry) {
            Ranking::query()->create([
                'competition_id' => $competition->id,
                'rankable_type' => $entry['type'],
                'rankable_id' => $entry['id'],
                'position' => $position++,
                'points' => $entry['points'],
                'played' => $entry['played'],
                'won' => $entry['won'],
                'drawn' => $entry['drawn'],
                'lost' => $entry['lost'],
                'scored_for' => $entry['scored_for'],
                'scored_against' => $entry['scored_against'],
            ]);
        }
    }

    /**
     * @return array{type: string, id: int, points: int, played: int, won: int, drawn: int, lost: int, scored_for: int, scored_against: int}
     */
    private function emptyStats(MatchParticipant $participant): array
    {
        return [
            'type' => $participant->participant_type,
            'id' => $participant->participant_id,
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

    /**
     * @param  array<string, array{type: string, id: int, points: int, played: int, won: int, drawn: int, lost: int, scored_for: int, scored_against: int}>  $stats
     */
    private function applySwissStandings(Competition $competition, array $stats): void
    {
        $competition->rankings()->delete();

        $entries = \App\Models\CompetitionParticipant::query()
            ->where('competition_id', $competition->id)
            ->orderByDesc('swiss_points')
            ->orderBy('seed')
            ->get();

        $position = 1;

        foreach ($entries as $entry) {
            $key = "{$entry->participant_type}:{$entry->participant_id}";
            $matchStats = $stats[$key] ?? null;

            Ranking::query()->create([
                'competition_id' => $competition->id,
                'rankable_type' => $entry->participant_type,
                'rankable_id' => $entry->participant_id,
                'position' => $position++,
                'points' => (int) $entry->swiss_points,
                'played' => $matchStats['played'] ?? 0,
                'won' => $matchStats['won'] ?? 0,
                'drawn' => $matchStats['drawn'] ?? 0,
                'lost' => $matchStats['lost'] ?? 0,
                'scored_for' => $matchStats['scored_for'] ?? 0,
                'scored_against' => $matchStats['scored_against'] ?? 0,
            ]);
        }
    }
}