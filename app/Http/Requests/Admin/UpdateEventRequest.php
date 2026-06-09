<?php

namespace App\Http\Requests\Admin;

use App\Enums\EventCadence;
use App\Enums\EventStatus;
use App\Enums\ParticipantUnitLabel;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event instanceof Event
            && ($this->user()?->can('update', $event) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Event $event */
        $event = $this->route('event');

        return [
            'event_type_id' => ['required', 'integer', Rule::exists('event_types', 'id')],
            'event_category_id' => ['required', 'integer', Rule::exists('event_categories', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('events', 'slug')
                    ->where('organization_id', $event->organization_id)
                    ->ignore($event->id),
            ],
            'status' => ['required', Rule::enum(EventStatus::class)],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'edition_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'cadence' => ['nullable', Rule::enum(EventCadence::class)],
            'participant_unit_label' => ['nullable', Rule::enum(ParticipantUnitLabel::class)],
            'event_series_id' => [
                'nullable',
                'integer',
                Rule::exists('event_series', 'id')->where('organization_id', $event->organization_id),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var Event $event */
            $event = $this->route('event');
            $newStatus = EventStatus::tryFrom($this->string('status')->toString());

            if ($newStatus && ! $event->status->canTransitionTo($newStatus) && ! $this->user()?->isSystemOwner()) {
                $validator->errors()->add(
                    'status',
                    "Cannot transition from {$event->status->value} to {$newStatus->value}.",
                );
            }
        });
    }
}