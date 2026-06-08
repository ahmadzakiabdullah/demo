<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Support\Permissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permissions::slug('organizations', 'create')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:organizations,slug'],
            'type' => ['required', Rule::enum(OrganizationType::class)],
            'timezone' => ['required', 'string', 'max:50', 'timezone:all'],
            'locale' => ['required', 'string', 'max:10'],
            'status' => ['required', Rule::enum(OrganizationStatus::class)],
        ];
    }
}