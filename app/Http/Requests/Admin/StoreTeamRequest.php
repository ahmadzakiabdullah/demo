<?php

namespace App\Http\Requests\Admin;

use App\Models\Event;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\SportDivision;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event instanceof Event
            && ($this->user()?->can('create', [Team::class, $event]) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Event $event */
        $event = $this->route('event');

        return [
            'event_participant_id' => [
                'required',
                'integer',
                Rule::exists('event_participants', 'id')->where('event_id', $event->id),
            ],
            'sport_id' => [
                'required',
                'integer',
                Rule::exists('sports', 'id')->where('event_id', $event->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
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
            'coach_user_id' => [
                'nullable',
                'integer',
                Rule::exists('organization_user', 'user_id')->where('organization_id', $event->organization_id),
            ],
            'manager_user_id' => [
                'nullable',
                'integer',
                Rule::exists('organization_user', 'user_id')->where('organization_id', $event->organization_id),
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator($validator): void
    {
        /** @var Event $event */
        $event = $this->route('event');

        $validator->after(function ($validator) use ($event) {
            if ($this->filled('sport_id') && $this->filled('slug')) {
                $exists = Team::withTrashed()
                    ->where('event_id', $event->id)
                    ->where('sport_id', $this->integer('sport_id'))
                    ->where('slug', $this->string('slug')->toString())
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('slug', 'Slug is already used for this sport.');
                }
            }

            if ($this->filled('sport_category_id') && $this->filled('sport_id')) {
                $sportId = (int) $this->input('sport_id');
                $category = SportCategory::query()
                    ->with('discipline')
                    ->find($this->input('sport_category_id'));

                if ($category === null || $category->discipline?->sport_id !== $sportId) {
                    $validator->errors()->add('sport_category_id', 'Category does not belong to the selected sport.');
                }
            }

            if ($this->filled('sport_division_id') && $this->filled('sport_category_id')) {
                $categoryId = (int) $this->input('sport_category_id');
                $division = SportDivision::query()->find($this->input('sport_division_id'));

                if ($division === null || $division->sport_category_id !== $categoryId) {
                    $validator->errors()->add('sport_division_id', 'Division does not belong to the selected category.');
                }
            }
        });
    }
}