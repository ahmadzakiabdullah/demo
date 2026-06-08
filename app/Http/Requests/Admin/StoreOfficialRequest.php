<?php

namespace App\Http\Requests\Admin;

use App\Enums\OfficialType;
use App\Models\Event;
use App\Models\Official;
use App\Models\Registration;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\SportDivision;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOfficialRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event instanceof Event
            && ($this->user()?->can('create', [Official::class, $event]) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Event $event */
        $event = $this->route('event');

        return [
            'existing_official_id' => [
                'nullable',
                'integer',
                Rule::exists('officials', 'id')->where('organization_id', $event->organization_id),
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
            'name' => ['required_without:existing_official_id', 'nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'type' => ['required_without:existing_official_id', 'nullable', Rule::enum(OfficialType::class)],
            'certification_level' => ['nullable', 'string', 'max:100'],
            'certification_expires_at' => ['nullable', 'date'],
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('organization_user', 'user_id')->where('organization_id', $event->organization_id),
            ],
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

            if ($this->filled('existing_official_id') && $this->filled('sport_id')) {
                $exists = Registration::query()
                    ->where('event_id', $event->id)
                    ->where('sport_id', $this->integer('sport_id'))
                    ->where('registrable_type', Official::class)
                    ->where('registrable_id', $this->integer('existing_official_id'))
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('sport_id', 'Official is already registered for this sport.');
                }
            }
        });
    }
}