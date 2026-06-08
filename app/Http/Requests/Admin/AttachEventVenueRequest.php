<?php

namespace App\Http\Requests\Admin;

use App\Models\Event;
use App\Models\Venue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachEventVenueRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event instanceof Event
            && ($this->user()?->can('attach', [Venue::class, $event]) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Event $event */
        $event = $this->route('event');

        return [
            'venue_id' => [
                'required',
                'integer',
                Rule::exists('venues', 'id')->where('organization_id', $event->organization_id),
                Rule::unique('event_venue', 'venue_id')->where('event_id', $event->id),
            ],
            'is_primary' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}