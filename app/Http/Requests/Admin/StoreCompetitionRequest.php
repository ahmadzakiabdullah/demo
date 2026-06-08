<?php

namespace App\Http\Requests\Admin;

use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompetitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event instanceof Event
            && ($this->user()?->can('create', [Competition::class, $event]) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Event $event */
        $event = $this->route('event');

        return [
            'sport_id' => [
                'required',
                'integer',
                Rule::exists('sports', 'id')->where('event_id', $event->id),
            ],
            'competition_format_id' => [
                'required',
                'integer',
                Rule::exists('competition_formats', 'id'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash'],
            'status' => ['nullable', Rule::in(CompetitionStatus::values())],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator($validator): void
    {
        /** @var Event $event */
        $event = $this->route('event');

        $validator->after(function ($validator) use ($event) {
            if ($this->filled('sport_id') && $this->filled('slug')) {
                $exists = Competition::withTrashed()
                    ->where('event_id', $event->id)
                    ->where('sport_id', $this->integer('sport_id'))
                    ->where('slug', $this->string('slug')->toString())
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('slug', 'Slug is already used for this sport.');
                }
            }
        });
    }
}