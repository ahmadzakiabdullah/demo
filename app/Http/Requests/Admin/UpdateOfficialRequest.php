<?php

namespace App\Http\Requests\Admin;

use App\Enums\OfficialType;
use App\Models\Event;
use App\Models\Official;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOfficialRequest extends FormRequest
{
    public function authorize(): bool
    {
        $official = $this->route('official');

        return $official instanceof Official
            && ($this->user()?->can('update', $official) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Event $event */
        $event = $this->route('event');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'type' => ['required', Rule::enum(OfficialType::class)],
            'certification_level' => ['nullable', 'string', 'max:100'],
            'certification_expires_at' => ['nullable', 'date'],
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('organization_user', 'user_id')->where('organization_id', $event->organization_id),
            ],
        ];
    }
}