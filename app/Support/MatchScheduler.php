<?php

namespace App\Support;

use App\Enums\MatchOfficialRole;
use App\Enums\MatchStatus;
use App\Models\Fixture;
use App\Models\MatchGame;

class MatchScheduler
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(Fixture $fixture, array $validated): MatchGame
    {
        $match = MatchGame::query()->create([
            'fixture_id' => $fixture->id,
            'venue_id' => $validated['venue_id'] ?? null,
            'facility_id' => $validated['facility_id'] ?? null,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'duration_minutes' => $validated['duration_minutes'] ?? 60,
            'status' => $validated['status'] ?? MatchStatus::Scheduled->value,
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->syncParticipants($match, $validated['participants']);
        $this->syncOfficials($match, $validated['officials'] ?? []);

        return $match;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(MatchGame $match, array $validated): MatchGame
    {
        $match->update(collect($validated)->only([
            'venue_id',
            'facility_id',
            'scheduled_at',
            'duration_minutes',
            'status',
            'notes',
        ])->all());

        if (array_key_exists('participants', $validated)) {
            $this->syncParticipants($match, $validated['participants']);
        }

        if (array_key_exists('officials', $validated)) {
            $this->syncOfficials($match, $validated['officials'] ?? []);
        }

        return $match;
    }

    /**
     * @param  list<array{side: string, participant_type: string, participant_id: int}>  $participants
     */
    private function syncParticipants(MatchGame $match, array $participants): void
    {
        $match->participants()->delete();

        foreach ($participants as $index => $participant) {
            $match->participants()->create([
                'participant_type' => $participant['participant_type'],
                'participant_id' => $participant['participant_id'],
                'side' => $participant['side'],
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * @param  list<array{official_id: int, role?: string}>  $officials
     */
    private function syncOfficials(MatchGame $match, array $officials): void
    {
        $match->officials()->delete();

        foreach ($officials as $official) {
            $match->officials()->create([
                'official_id' => $official['official_id'],
                'role' => $official['role'] ?? MatchOfficialRole::Referee->value,
            ]);
        }
    }
}