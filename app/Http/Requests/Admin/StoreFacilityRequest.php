<?php

namespace App\Http\Requests\Admin;

use App\Enums\FacilityType;
use App\Models\Facility;
use App\Models\Venue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFacilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $venue = $this->route('venue');

        return $venue instanceof Venue
            && ($this->user()?->can('update', $venue) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Venue $venue */
        $venue = $this->route('venue');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('facilities', 'slug')->where('venue_id', $venue->id),
            ],
            'type' => ['required', Rule::enum(FacilityType::class)],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}