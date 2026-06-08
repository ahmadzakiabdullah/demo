<?php

namespace App\Http\Requests\Admin;

use App\Enums\ResultStatus;
use App\Models\Result;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateResultStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $result = $this->route('result');

        return $result instanceof Result
            && ($this->user()?->can('confirm', $result) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([ResultStatus::Confirmed->value, ResultStatus::Published->value])],
        ];
    }
}