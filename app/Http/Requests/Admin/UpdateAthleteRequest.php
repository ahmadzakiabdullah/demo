<?php

namespace App\Http\Requests\Admin;

use App\Enums\SportGender;
use App\Models\Athlete;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAthleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $athlete = $this->route('athlete');

        return $athlete instanceof Athlete
            && ($this->user()?->can('update', $athlete) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Event $event */
        $event = $this->route('event');
        /** @var Athlete $athlete */
        $athlete = $this->route('athlete');

        return [
            'name' => ['required', 'string', 'max:255'],
            'dob' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::enum(SportGender::class)],
            'nationality' => ['nullable', 'string', 'max:100'],
            'id_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('athletes', 'id_number')
                    ->where('organization_id', $event->organization_id)
                    ->ignore($athlete->id),
            ],
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('organization_user', 'user_id')->where('organization_id', $event->organization_id),
            ],
            'medical_clearance' => ['nullable', 'boolean'],
        ];
    }
}