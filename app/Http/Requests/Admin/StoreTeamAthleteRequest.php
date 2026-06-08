<?php

namespace App\Http\Requests\Admin;

use App\Enums\TeamMemberRole;
use App\Models\Athlete;
use App\Models\Event;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamAthleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $team = $this->route('team');

        return $team instanceof Team
            && ($this->user()?->can('manageRoster', $team) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Event $event */
        $event = $this->route('event');
        /** @var Team $team */
        $team = $this->route('team');

        return [
            'athlete_id' => [
                'required',
                'integer',
                Rule::exists('athletes', 'id')->where('organization_id', $event->organization_id),
                Rule::unique('team_athlete', 'athlete_id')->where('team_id', $team->id),
            ],
            'role' => ['required', Rule::enum(TeamMemberRole::class)],
            'jersey_number' => ['nullable', 'string', 'max:10'],
        ];
    }
}