<?php

namespace App\Http\Requests\Admin;

use App\Enums\AppealStatus;
use App\Models\ResultAppeal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateResultAppealStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $appeal = $this->route('appeal');

        return $appeal instanceof ResultAppeal
            && ($this->user()?->can('resolve', $appeal) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                AppealStatus::UnderReview->value,
                AppealStatus::Upheld->value,
                AppealStatus::Overturned->value,
            ])],
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
            'proposed_home_score' => [
                Rule::requiredIf(fn () => $this->input('status') === AppealStatus::Overturned->value),
                'nullable',
                'integer',
                'min:0',
                'max:999',
            ],
            'proposed_away_score' => [
                Rule::requiredIf(fn () => $this->input('status') === AppealStatus::Overturned->value),
                'nullable',
                'integer',
                'min:0',
                'max:999',
            ],
        ];
    }
}