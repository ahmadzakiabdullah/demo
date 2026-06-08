<?php

namespace App\Http\Requests\Admin;

use App\Enums\MatchOfficialRole;
use App\Enums\MatchParticipantSide;
use App\Enums\MatchStatus;
use App\Models\Athlete;
use App\Models\Competition;
use App\Models\Facility;
use App\Models\Fixture;
use App\Models\Official;
use App\Models\Team;
use App\Support\ScheduleConflictDetector;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class StoreMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $competition = $this->route('competition');

        return $competition instanceof Competition
            && ($this->user()?->can('manageSchedule', $competition) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Competition $competition */
        $competition = $this->route('competition');
        $event = $competition->event;
        $eventVenueIds = $event?->venues()->pluck('venues.id')->all() ?? [];

        return [
            'scheduled_at' => ['nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:480'],
            'venue_id' => [
                'nullable',
                'integer',
                Rule::in($eventVenueIds),
            ],
            'facility_id' => [
                'nullable',
                'integer',
                Rule::exists('facilities', 'id'),
            ],
            'status' => ['nullable', Rule::in(MatchStatus::values())],
            'notes' => ['nullable', 'string', 'max:2000'],
            'participants' => ['required', 'array', 'size:2'],
            'participants.*.side' => ['required', Rule::in(MatchParticipantSide::values())],
            'participants.*.participant_type' => ['required', Rule::in([Team::class, Athlete::class])],
            'participants.*.participant_id' => ['required', 'integer', 'min:1'],
            'officials' => ['nullable', 'array'],
            'officials.*.official_id' => ['required', 'integer', Rule::exists('officials', 'id')],
            'officials.*.role' => ['nullable', Rule::in(MatchOfficialRole::values())],
        ];
    }

    public function withValidator($validator): void
    {
        /** @var Competition $competition */
        $competition = $this->route('competition');
        $event = $competition->event;

        $validator->after(function ($validator) use ($competition, $event) {
            if ($this->filled('facility_id') && $this->filled('venue_id')) {
                $facility = Facility::query()->find($this->integer('facility_id'));

                if ($facility === null || $facility->venue_id !== $this->integer('venue_id')) {
                    $validator->errors()->add('facility_id', 'Facility does not belong to the selected venue.');
                }
            }

            $sides = collect($this->input('participants', []))->pluck('side');
            if ($sides->unique()->count() !== 2) {
                $validator->errors()->add('participants', 'Matches require one home and one away participant.');
            }

            foreach ($this->input('participants', []) as $index => $participant) {
                $type = $participant['participant_type'] ?? null;
                $id = (int) ($participant['participant_id'] ?? 0);

                if ($type === Team::class) {
                    $valid = Team::query()
                        ->where('event_id', $event->id)
                        ->where('sport_id', $competition->sport_id)
                        ->where('id', $id)
                        ->exists();

                    if (! $valid) {
                        $validator->errors()->add("participants.{$index}.participant_id", 'Team is not registered for this competition sport.');
                    }
                }

                if ($type === Athlete::class) {
                    $valid = Athlete::query()
                        ->where('organization_id', $event->organization_id)
                        ->where('id', $id)
                        ->exists();

                    if (! $valid) {
                        $validator->errors()->add("participants.{$index}.participant_id", 'Athlete does not belong to this organization.');
                    }
                }
            }

            foreach ($this->input('officials', []) as $index => $official) {
                $valid = Official::query()
                    ->where('organization_id', $event->organization_id)
                    ->where('id', (int) ($official['official_id'] ?? 0))
                    ->exists();

                if (! $valid) {
                    $validator->errors()->add("officials.{$index}.official_id", 'Official does not belong to this organization.');
                }
            }

            if (! $this->filled('scheduled_at')) {
                return;
            }

            $scheduledAt = Carbon::parse($this->input('scheduled_at'));
            $duration = $this->integer('duration_minutes', 60);

            $conflicts = app(ScheduleConflictDetector::class)->detect(
                $scheduledAt,
                $duration,
                $this->filled('venue_id') ? $this->integer('venue_id') : null,
                $this->filled('facility_id') ? $this->integer('facility_id') : null,
                $this->input('participants', []),
                $this->input('officials', []),
            );

            foreach ($conflicts['venue'] as $message) {
                $validator->errors()->add('venue_id', $message);
            }

            foreach ($conflicts['officials'] as $message) {
                $validator->errors()->add('officials', $message);
            }

            foreach ($conflicts['athletes'] as $message) {
                $validator->errors()->add('participants', $message);
            }
        });
    }
}