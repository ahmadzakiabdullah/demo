<?php

namespace App\Http\Requests\Admin;

use App\Enums\SportStatus;
use App\Models\Sport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $sport = $this->route('sport');

        return $sport instanceof Sport
            && ($this->user()?->can('update', $sport) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Sport $sport */
        $sport = $this->route('sport');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('sports', 'slug')
                    ->where('event_id', $sport->event_id)
                    ->ignore($sport->id),
            ],
            'status' => ['required', Rule::enum(SportStatus::class)],
            'rules' => ['nullable', 'array'],
        ];
    }
}