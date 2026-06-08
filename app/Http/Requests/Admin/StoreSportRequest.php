<?php

namespace App\Http\Requests\Admin;

use App\Enums\SportStatus;
use App\Models\Event;
use App\Models\Sport;
use App\Support\Permissions;
use App\Support\SportTemplates;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event instanceof Event
            && ($this->user()?->can('create', [Sport::class, $event]) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Event $event */
        $event = $this->route('event');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('sports', 'slug')->where('event_id', $event->id),
            ],
            'template_slug' => ['nullable', 'string', Rule::in(array_column(SportTemplates::all(), 'slug'))],
            'status' => ['required', Rule::enum(SportStatus::class)],
            'rules' => ['nullable', 'array'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('template_slug') && $this->filled('name')) {
                return;
            }

            if ($this->filled('template_slug') && ! $this->filled('name')) {
                return;
            }
        });
    }
}