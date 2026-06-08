<?php

namespace App\Http\Requests\Admin;

use App\Models\Event;
use App\Models\Sport;
use App\Models\Venue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LinkEventSportVenueRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event instanceof Event
            && ($this->user()?->can('manageAtEvent', [Venue::class, $event]) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Event $event */
        $event = $this->route('event');
        /** @var Venue $venue */
        $venue = $this->route('venue');

        return [
            'sport_id' => [
                'required',
                'integer',
                Rule::exists('sports', 'id')->where('event_id', $event->id),
                Rule::unique('event_sport_venue', 'sport_id')
                    ->where('event_id', $event->id)
                    ->where('venue_id', $venue->id),
            ],
        ];
    }
}