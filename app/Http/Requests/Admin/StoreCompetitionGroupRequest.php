<?php

namespace App\Http\Requests\Admin;

use App\Models\Competition;
use App\Models\CompetitionGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompetitionGroupRequest extends FormRequest
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

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    public function withValidator($validator): void
    {
        /** @var Competition $competition */
        $competition = $this->route('competition');

        $validator->after(function ($validator) use ($competition) {
            if (! $competition->format?->supportsGroups()) {
                $validator->errors()->add('name', 'Groups are only supported for group stage competitions.');
            }

            if ($this->filled('slug')) {
                $exists = CompetitionGroup::query()
                    ->where('competition_id', $competition->id)
                    ->where('slug', $this->string('slug')->toString())
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('slug', 'Slug is already used in this competition.');
                }
            }
        });
    }
}