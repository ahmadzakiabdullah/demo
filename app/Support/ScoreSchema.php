<?php

namespace App\Support;

use App\Models\Sport;

class ScoreSchema
{
    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'type' => 'goals',
            'label' => 'Goals',
            'fields' => [
                ['key' => 'home_score', 'label' => 'Home', 'input' => 'integer', 'min' => 0, 'max' => 999],
                ['key' => 'away_score', 'label' => 'Away', 'input' => 'integer', 'min' => 0, 'max' => 999],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function forSport(?Sport $sport): array
    {
        if ($sport?->score_schema !== null && $sport->score_schema !== []) {
            return array_replace_recursive(self::defaults(), $sport->score_schema);
        }

        return match ($sport?->template_slug) {
            'badminton', 'tennis' => [
                'type' => 'sets',
                'label' => 'Sets',
                'best_of' => 3,
                'fields' => [
                    ['key' => 'home_score', 'label' => 'Home sets', 'input' => 'integer', 'min' => 0, 'max' => 3],
                    ['key' => 'away_score', 'label' => 'Away sets', 'input' => 'integer', 'min' => 0, 'max' => 3],
                ],
            ],
            'swimming', 'athletics' => [
                'type' => 'time',
                'label' => 'Time (seconds)',
                'lower_is_better' => true,
                'fields' => [
                    ['key' => 'home_score', 'label' => 'Home time', 'input' => 'number', 'min' => 0, 'step' => 0.01],
                    ['key' => 'away_score', 'label' => 'Away time', 'input' => 'number', 'min' => 0, 'step' => 0.01],
                ],
            ],
            default => self::defaults(),
        };
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $payload
     * @return array{home_score: int|float, away_score: int|float, winner_side: ?string}
     */
    public static function normalizeResult(array $schema, array $payload): array
    {
        $home = $payload['home_score'] ?? 0;
        $away = $payload['away_score'] ?? 0;
        $lowerIsBetter = (bool) ($schema['lower_is_better'] ?? false);

        $winnerSide = match (true) {
            $home === $away => null,
            $lowerIsBetter => $home < $away ? 'home' : 'away',
            default => $home > $away ? 'home' : 'away',
        };

        return [
            'home_score' => is_numeric($home) ? $home + 0 : 0,
            'away_score' => is_numeric($away) ? $away + 0 : 0,
            'winner_side' => $winnerSide,
        ];
    }
}