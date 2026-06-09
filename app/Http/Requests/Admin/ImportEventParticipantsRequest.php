<?php

namespace App\Http\Requests\Admin;

use App\Models\Event;
use App\Models\EventParticipant;
use Illuminate\Foundation\Http\FormRequest;

class ImportEventParticipantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event instanceof Event
            && ($this->user()?->can('create', [EventParticipant::class, $event]) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ];
    }
}