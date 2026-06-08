<?php

namespace App\Http\Requests\Admin;

use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompetitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $competition = $this->route('competition');

        return $competition instanceof Competition
            && ($this->user()?->can('update', $competition) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Competition $competition */
        $competition = $this->route('competition');

        return [
            'competition_format_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('competition_formats', 'id'),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', 'alpha_dash'],
            'status' => ['sometimes', Rule::in(CompetitionStatus::values())],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator($validator): void
    {
        /** @var Competition $competition */
        $competition = $this->route('competition');

        $validator->after(function ($validator) use ($competition) {
            if ($this->filled('slug')) {
                $exists = Competition::withTrashed()
                    ->where('event_id', $competition->event_id)
                    ->where('sport_id', $competition->sport_id)
                    ->where('slug', $this->string('slug')->toString())
                    ->where('id', '!=', $competition->id)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('slug', 'Slug is already used for this sport.');
                }
            }
        });
    }
}