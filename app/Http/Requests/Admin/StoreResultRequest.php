<?php

namespace App\Http\Requests\Admin;

use App\Models\MatchGame;
use Illuminate\Foundation\Http\FormRequest;

class StoreResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        $match = $this->route('matchGame');

        return $match instanceof MatchGame
            && ($this->user()?->can('enterResult', $match) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'home_score' => ['required', 'integer', 'min:0', 'max:999'],
            'away_score' => ['required', 'integer', 'min:0', 'max:999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}