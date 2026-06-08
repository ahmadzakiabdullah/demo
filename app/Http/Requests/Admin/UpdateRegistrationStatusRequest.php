<?php

namespace App\Http\Requests\Admin;

use App\Enums\RegistrationStatus;
use App\Models\Registration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRegistrationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $registration = $this->route('registration');

        return $registration instanceof Registration
            && ($this->user()?->can('updateStatus', $registration) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(RegistrationStatus::class)],
            'rejected_reason' => [
                Rule::requiredIf(fn () => $this->input('status') === RegistrationStatus::Rejected->value),
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}