<?php

namespace App\Support;

use App\Models\Competition;

class RankingRules
{
    /**
     * @return array{points_win: int, points_draw: int, points_loss: int, tiebreakers: list<string>}
     */
    public static function forCompetition(Competition $competition): array
    {
        $settings = $competition->settings ?? [];
        $ranking = $settings['ranking'] ?? [];

        return [
            'points_win' => (int) ($ranking['points_win'] ?? 3),
            'points_draw' => (int) ($ranking['points_draw'] ?? 1),
            'points_loss' => (int) ($ranking['points_loss'] ?? 0),
            'tiebreakers' => $ranking['tiebreakers'] ?? ['goal_difference', 'goals_for'],
        ];
    }
}