<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Support\Permissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permissions::slug('organizations', 'update')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $organization = $this->route('organization');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('organizations', 'slug')->ignore($organization?->id),
            ],
            'type' => ['required', Rule::enum(OrganizationType::class)],
            'timezone' => ['required', 'string', 'max:50', 'timezone:all'],
            'locale' => ['required', 'string', 'max:10'],
            'status' => ['required', Rule::enum(OrganizationStatus::class)],
        ];
    }
}