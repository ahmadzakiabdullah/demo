<?php

namespace App\Http\Requests\Admin;

use App\Enums\SportGender;
use App\Models\Athlete;
use App\Models\Event;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\Registration;
use App\Models\SportDivision;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAthleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event instanceof Event
            && ($this->user()?->can('create', [Athlete::class, $event]) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Event $event */
        $event = $this->route('event');

        return [
            'existing_athlete_id' => [
                'nullable',
                'integer',
                Rule::exists('athletes', 'id')->where('organization_id', $event->organization_id),
            ],
            'sport_id' => [
                'required',
                'integer',
                Rule::exists('sports', 'id')->where('event_id', $event->id),
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
            'notes' => ['nullable', 'string', 'max:2000'],
            'name' => ['required_without:existing_athlete_id', 'nullable', 'string', 'max:255'],
            'dob' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::enum(SportGender::class)],
            'nationality' => ['nullable', 'string', 'max:100'],
            'id_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('athletes', 'id_number')->where('organization_id', $event->organization_id),
            ],
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('organization_user', 'user_id')->where('organization_id', $event->organization_id),
            ],
            'medical_clearance' => ['nullable', 'boolean'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
        ];
    }

    public function withValidator($validator): void
    {
        /** @var Event $event */
        $event = $this->route('event');

        $validator->after(function ($validator) use ($event) {
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

            if ($this->filled('existing_athlete_id') && $this->filled('sport_id')) {
                $exists = Registration::query()
                    ->where('event_id', $event->id)
                    ->where('sport_id', $this->integer('sport_id'))
                    ->where('registrable_type', Athlete::class)
                    ->where('registrable_id', $this->integer('existing_athlete_id'))
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('sport_id', 'Athlete is already registered for this sport.');
                }
            }
        });
    }
}