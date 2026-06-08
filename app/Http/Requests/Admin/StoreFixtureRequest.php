<?php

namespace App\Http\Requests\Admin;

use App\Models\Competition;
use App\Models\CompetitionGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFixtureRequest extends FormRequest
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
            'round' => ['nullable', 'string', 'max:100'],
            'group_id' => [
                'nullable',
                'integer',
                Rule::exists('groups', 'id')->where('competition_id', $competition->id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    public function withValidator($validator): void
    {
        /** @var Competition $competition */
        $competition = $this->route('competition');

        $validator->after(function ($validator) use ($competition) {
            if ($this->filled('group_id') && ! $competition->format?->supportsGroups()) {
                $validator->errors()->add('group_id', 'Groups are only supported for group stage competitions.');
            }
        });
    }
}