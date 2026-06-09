<?php

namespace App\Http\Requests\Admin;

use App\Enums\EventParticipantStatus;
use App\Enums\EventParticipantType;
use App\Models\Event;
use App\Models\EventParticipant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        $participant = $this->route('participant');

        return $participant instanceof EventParticipant
            && ($this->user()?->can('update', $participant) ?? false);
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
            'type' => ['required', Rule::enum(EventParticipantType::class)],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:20',
                'alpha_dash',
                Rule::unique('event_participants', 'code')
                    ->where('event_id', $event->id)
                    ->ignore($participant->id),
            ],
            'branch_id' => [
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where('organization_id', $event->organization_id),
            ],
            'status' => ['required', Rule::enum(EventParticipantStatus::class)],
            'metadata' => ['nullable', 'array'],
        ];
    }
}