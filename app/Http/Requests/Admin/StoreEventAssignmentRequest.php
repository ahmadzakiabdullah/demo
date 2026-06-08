<?php

namespace App\Http\Requests\Admin;

use App\Enums\EventAssignmentRole;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event instanceof Event
            && ($this->user()?->can('manageAssignments', $event) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Event $event */
        $event = $this->route('event');

        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
                Rule::exists('organization_user', 'user_id')
                    ->where('organization_id', $event->organization_id),
            ],
            'role' => ['required', Rule::enum(EventAssignmentRole::class)],
        ];
    }
}