<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAccreditationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // stub, use policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'accreditable_type' => ['required', 'string', 'in:App\\Models\\EventParticipant,App\\Models\\Team,App\\Models\\Athlete,App\\Models\\Official'],
            'accreditable_id' => ['required', 'integer'],
            'type' => ['required', 'string', 'in:athlete,official,volunteer,media'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
