<?php

namespace App\Http\Requests\Admin;

use App\Enums\MatchOfficialRole;
use App\Enums\MatchParticipantSide;
use App\Enums\MatchStatus;
use App\Models\Athlete;
use App\Models\Competition;
use App\Models\Facility;
use App\Models\MatchGame;
use App\Models\Official;
use App\Models\Team;
use App\Support\ScheduleConflictDetector;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class UpdateMatchRequest extends FormRequest
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
            'scheduled_at' => ['sometimes', 'nullable', 'date'],
            'duration_minutes' => ['sometimes', 'integer', 'min:15', 'max:480'],
            'venue_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::in($eventVenueIds),
            ],
            'facility_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('facilities', 'id'),
            ],
            'status' => ['sometimes', Rule::in(MatchStatus::values())],
            'notes' => ['nullable', 'string', 'max:2000'],
            'participants' => ['sometimes', 'array', 'size:2'],
            'participants.*.side' => ['required_with:participants', Rule::in(MatchParticipantSide::values())],
            'participants.*.participant_type' => ['required_with:participants', Rule::in([Team::class, Athlete::class])],
            'participants.*.participant_id' => ['required_with:participants', 'integer', 'min:1'],
            'officials' => ['sometimes', 'nullable', 'array'],
            'officials.*.official_id' => ['required_with:officials', 'integer', Rule::exists('officials', 'id')],
            'officials.*.role' => ['nullable', Rule::in(MatchOfficialRole::values())],
        ];
    }

    public function withValidator($validator): void
    {
        /** @var Competition $competition */
        $competition = $this->route('competition');
        /** @var MatchGame $match */
        $match = $this->route('matchGame');
        $event = $competition->event;

        $validator->after(function ($validator) use ($competition, $event, $match) {
            $venueId = $this->has('venue_id') ? $this->input('venue_id') : $match->venue_id;
            $facilityId = $this->has('facility_id') ? $this->input('facility_id') : $match->facility_id;

            if ($facilityId && $venueId) {
                $facility = Facility::query()->find((int) $facilityId);

                if ($facility === null || $facility->venue_id !== (int) $venueId) {
                    $validator->errors()->add('facility_id', 'Facility does not belong to the selected venue.');
                }
            }

            if ($this->has('participants')) {
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
            }

            if ($this->has('officials')) {
                foreach ($this->input('officials', []) as $index => $official) {
                    $valid = Official::query()
                        ->where('organization_id', $event->organization_id)
                        ->where('id', (int) ($official['official_id'] ?? 0))
                        ->exists();

                    if (! $valid) {
                        $validator->errors()->add("officials.{$index}.official_id", 'Official does not belong to this organization.');
                    }
                }
            }

            $scheduledAtInput = $this->has('scheduled_at')
                ? $this->input('scheduled_at')
                : $match->scheduled_at?->toDateTimeString();

            if ($scheduledAtInput === null) {
                return;
            }

            $scheduledAt = Carbon::parse($scheduledAtInput);
            $duration = $this->integer('duration_minutes', $match->duration_minutes);

            $participants = $this->has('participants')
                ? $this->input('participants', [])
                : $match->participants->map(fn ($participant) => [
                    'participant_type' => $participant->participant_type,
                    'participant_id' => $participant->participant_id,
                ])->all();

            $officials = $this->has('officials')
                ? $this->input('officials', [])
                : $match->officials->map(fn ($official) => [
                    'official_id' => $official->official_id,
                    'role' => $official->role?->value,
                ])->all();

            $conflicts = app(ScheduleConflictDetector::class)->detect(
                $scheduledAt,
                $duration,
                $venueId ? (int) $venueId : null,
                $facilityId ? (int) $facilityId : null,
                $participants,
                $officials,
                $match->id,
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