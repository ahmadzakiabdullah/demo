<?php

namespace App\Http\Requests\Admin;

use App\Models\Organization;
use App\Models\Venue;
use App\Support\OrganizationContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVenueRequest extends FormRequest
{
    public function authorize(): bool
    {
        $organization = OrganizationContext::resolve($this);

        return $this->user()?->can('create', [Venue::class, $organization]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Organization $organization */
        $organization = OrganizationContext::resolve($this);

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('venues', 'slug')->where('organization_id', $organization->id),
            ],
            'address' => ['nullable', 'string', 'max:500'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }
}