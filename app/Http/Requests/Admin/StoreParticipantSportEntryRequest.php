<?php

namespace App\Http\Requests\Admin;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\ParticipantSportEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreParticipantSportEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $participant = $this->route('participant');

        return $participant instanceof EventParticipant
            && ($this->user()?->can('create', [ParticipantSportEntry::class, $participant]) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Event $event */
        $event = $this->route('event');
        /** @var EventParticipant $participant */
        $participant = $this->route('participant');

        return [
            'sport_id' => [
                'required',
                'integer',
                Rule::exists('sports', 'id')->where('event_id', $event->id),
            ],
            'sport_category_id' => [
                'nullable',
                'integer',
                Rule::exists('sport_categories', 'id'),
            ],
            'sport_division_id' => [
                'nullable',
                'integer',
                Rule::exists('sport_divisions', 'id'),
            ],
            'status' => ['required', Rule::enum(RegistrationStatus::class)],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var EventParticipant $participant */
            $participant = $this->route('participant');

            $exists = ParticipantSportEntry::query()
                ->where('event_participant_id', $participant->id)
                ->where('sport_id', $this->integer('sport_id'))
                ->where('sport_category_id', $this->input('sport_category_id'))
                ->where('sport_division_id', $this->input('sport_division_id'))
                ->exists();

            if ($exists) {
                $validator->errors()->add('sport_id', 'This participant already has an entry for this sport and category.');
            }
        });
    }
}