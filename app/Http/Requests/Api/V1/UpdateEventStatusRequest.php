<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event instanceof Event
            && ($this->user()?->can('update', $event) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(EventStatus::class)],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var Event $event */
            $event = $this->route('event');
            $newStatus = EventStatus::tryFrom($this->string('status')->toString());

            if ($newStatus && ! $event->status->canTransitionTo($newStatus) && ! $this->user()?->isSystemOwner()) {
                $validator->errors()->add(
                    'status',
                    "Cannot transition from {$event->status->value} to {$newStatus->value}.",
                );
            }
        });
    }
}