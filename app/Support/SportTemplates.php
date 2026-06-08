<?php

namespace App\Support;

class SportTemplates
{
    /**
     * @return list<array{slug: string, name: string, rules: array<string, mixed>, disciplines: list<array<string, mixed>>}>
     */
    public static function all(): array
    {
        return [
            self::football(),
            self::badminton(),
            self::swimming(),
            self::athletics(),
            self::esports(),
        ];
    }

    public static function find(string $slug): ?array
    {
        foreach (self::all() as $template) {
            if ($template['slug'] === $slug) {
                return $template;
            }
        }

        return null;
    }

    /**
     * @return array{slug: string, name: string, rules: array<string, mixed>, disciplines: list<array<string, mixed>>}
     */
    private static function football(): array
    {
        return [
            'slug' => 'football',
            'name' => 'Football',
            'rules' => [
                'players_per_team' => 11,
                'match_duration_minutes' => 90,
                'format' => 'team',
            ],
            'disciplines' => [
                [
                    'name' => '11-a-side',
                    'slug' => '11-a-side',
                    'categories' => [
                        [
                            'name' => 'Men',
                            'slug' => 'men',
                            'gender' => 'male',
                            'divisions' => [
                                ['name' => 'Open', 'slug' => 'open'],
                                ['name' => 'U-21', 'slug' => 'u-21'],
                            ],
                        ],
                        [
                            'name' => 'Women',
                            'slug' => 'women',
                            'gender' => 'female',
                            'divisions' => [
                                ['name' => 'Open', 'slug' => 'open'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{slug: string, name: string, rules: array<string, mixed>, disciplines: list<array<string, mixed>>}
     */
    private static function badminton(): array
    {
        return [
            'slug' => 'badminton',
            'name' => 'Badminton',
            'rules' => [
                'scoring_system' => 'rally_point_21',
                'format' => 'individual_and_team',
            ],
            'disciplines' => [
                [
                    'name' => 'Singles',
                    'slug' => 'singles',
                    'categories' => [
                        [
                            'name' => 'Men Singles',
                            'slug' => 'men-singles',
                            'gender' => 'male',
                            'divisions' => [
                                ['name' => 'Open', 'slug' => 'open'],
                                ['name' => 'U-18', 'slug' => 'u-18'],
                            ],
                        ],
                        [
                            'name' => 'Women Singles',
                            'slug' => 'women-singles',
                            'gender' => 'female',
                            'divisions' => [
                                ['name' => 'Open', 'slug' => 'open'],
                                ['name' => 'U-18', 'slug' => 'u-18'],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Doubles',
                    'slug' => 'doubles',
                    'categories' => [
                        [
                            'name' => 'Mixed Doubles',
                            'slug' => 'mixed-doubles',
                            'gender' => 'mixed',
                            'divisions' => [
                                ['name' => 'Open', 'slug' => 'open'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{slug: string, name: string, rules: array<string, mixed>, disciplines: list<array<string, mixed>>}
     */
    private static function swimming(): array
    {
        return [
            'slug' => 'swimming',
            'name' => 'Swimming',
            'rules' => [
                'pool_length_meters' => 50,
                'format' => 'individual',
            ],
            'disciplines' => [
                [
                    'name' => 'Freestyle',
                    'slug' => 'freestyle',
                    'categories' => [
                        [
                            'name' => '100m Freestyle',
                            'slug' => '100m-freestyle',
                            'gender' => 'open',
                            'divisions' => [
                                ['name' => 'Open', 'slug' => 'open'],
                                ['name' => 'U-18', 'slug' => 'u-18'],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Butterfly',
                    'slug' => 'butterfly',
                    'categories' => [
                        [
                            'name' => '100m Butterfly',
                            'slug' => '100m-butterfly',
                            'gender' => 'open',
                            'divisions' => [
                                ['name' => 'Open', 'slug' => 'open'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{slug: string, name: string, rules: array<string, mixed>, disciplines: list<array<string, mixed>>}
     */
    private static function athletics(): array
    {
        return [
            'slug' => 'athletics',
            'name' => 'Athletics',
            'rules' => [
                'format' => 'individual',
            ],
            'disciplines' => [
                [
                    'name' => 'Track',
                    'slug' => 'track',
                    'categories' => [
                        [
                            'name' => '100m Sprint',
                            'slug' => '100m-sprint',
                            'gender' => 'open',
                            'divisions' => [
                                ['name' => 'Open', 'slug' => 'open'],
                                ['name' => 'U-21', 'slug' => 'u-21'],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Field',
                    'slug' => 'field',
                    'categories' => [
                        [
                            'name' => 'Long Jump',
                            'slug' => 'long-jump',
                            'gender' => 'open',
                            'divisions' => [
                                ['name' => 'Open', 'slug' => 'open'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{slug: string, name: string, rules: array<string, mixed>, disciplines: list<array<string, mixed>>}
     */
    private static function esports(): array
    {
        return [
            'slug' => 'esports',
            'name' => 'Esports',
            'rules' => [
                'platform' => 'pc',
                'format' => 'team',
            ],
            'disciplines' => [
                [
                    'name' => 'MOBA',
                    'slug' => 'moba',
                    'categories' => [
                        [
                            'name' => 'Team 5v5',
                            'slug' => 'team-5v5',
                            'gender' => 'open',
                            'divisions' => [
                                ['name' => 'Open', 'slug' => 'open'],
                                ['name' => 'University', 'slug' => 'university'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}