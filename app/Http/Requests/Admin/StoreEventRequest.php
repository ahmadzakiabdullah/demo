<?php

namespace App\Http\Requests\Admin;

use App\Enums\EventCadence;
use App\Enums\EventStatus;
use App\Enums\ParticipantUnitLabel;
use App\Models\Organization;
use App\Support\Permissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Event::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = $this->integer('organization_id');

        return [
            'organization_id' => ['required', 'integer', Rule::exists('organizations', 'id')],
            'event_type_id' => ['required', 'integer', Rule::exists('event_types', 'id')],
            'event_category_id' => ['required', 'integer', Rule::exists('event_categories', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('events', 'slug')->where('organization_id', $organizationId),
            ],
            'status' => ['required', Rule::enum(EventStatus::class)],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'edition_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'cadence' => ['nullable', Rule::enum(EventCadence::class)],
            'participant_unit_label' => ['nullable', Rule::enum(ParticipantUnitLabel::class)],
            'event_series_id' => [
                'nullable',
                'integer',
                Rule::exists('event_series', 'id')->where('organization_id', $organizationId),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $organization = Organization::query()->find($this->integer('organization_id'));

            $user = $this->user();

            if (! $organization || ! $user) {
                return;
            }

            if (! $user->isSystemOwner()
                && (! $user->organizations()->where('organizations.id', $organization->id)->exists()
                    || ! $user->hasPermission(Permissions::slug('events', 'create'), $organization))) {
                $validator->errors()->add('organization_id', 'You are not allowed to create events for this organization.');
            }

            $status = EventStatus::tryFrom($this->string('status')->toString());

            if ($status && $status !== EventStatus::Draft && ! $this->user()?->isSystemOwner()) {
                if (! $this->user()?->hasPermission(Permissions::slug('events', 'manage'), $organization)) {
                    $validator->errors()->add('status', 'New events must start in draft status.');
                }
            }
        });
    }
}