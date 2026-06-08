<?php

namespace App\Http\Requests\Admin;

use App\Models\Result;
use App\Models\ResultAppeal;
use Illuminate\Foundation\Http\FormRequest;

class StoreResultAppealRequest extends FormRequest
{
    public function authorize(): bool
    {
        $result = $this->route('result');

        return $result instanceof Result
            && ($this->user()?->can('create', [ResultAppeal::class, $result]) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
            'proposed_home_score' => ['nullable', 'integer', 'min:0', 'max:999'],
            'proposed_away_score' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }
}