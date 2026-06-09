<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Database\Seeder;

class EventParticipantsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $modules = ['event_participants', 'participant_sport_entries'];

        $permissions = collect($modules)->flatMap(function (string $module) {
            return collect(Permissions::ACTIONS)->map(function (string $action) use ($module) {
                return Permission::query()->firstOrCreate(
                    ['slug' => Permissions::slug($module, $action)],
                    [
                        'name' => ucfirst($action).' '.str_replace('_', ' ', $module),
                        'module' => $module,
                    ],
                );
            });
        });

        $this->attachToRole(Role::SYSTEM_OWNER, $permissions->pluck('slug')->all(), $permissions);

        $participantSlugs = collect(Permissions::ACTIONS)->map(
            fn (string $action) => Permissions::slug('event_participants', $action),
        )->all();

        $entrySlugs = [
            Permissions::slug('participant_sport_entries', 'view'),
            Permissions::slug('participant_sport_entries', 'create'),
            Permissions::slug('participant_sport_entries', 'update'),
            Permissions::slug('participant_sport_entries', 'manage'),
        ];

        $this->attachToRole(Role::ORG_ADMIN, $participantSlugs, $permissions);
        $this->attachToRole(Role::ORG_ADMIN, $entrySlugs, $permissions);

        $this->attachToRole(Role::EVENT_ORGANIZER, [
            Permissions::slug('event_participants', 'view'),
            Permissions::slug('event_participants', 'manage'),
            Permissions::slug('participant_sport_entries', 'view'),
            Permissions::slug('participant_sport_entries', 'manage'),
        ], $permissions);

        $this->attachToRole(Role::TEAM_MANAGER, [
            Permissions::slug('event_participants', 'view'),
            Permissions::slug('participant_sport_entries', 'view'),
            Permissions::slug('participant_sport_entries', 'create'),
            Permissions::slug('participant_sport_entries', 'update'),
        ], $permissions);
    }

    /**
     * @param  list<string>  $slugs
     * @param  \Illuminate\Support\Collection<int, \App\Models\Permission>  $permissions
     */
    private function attachToRole(string $roleSlug, array $slugs, $permissions): void
    {
        $role = Role::query()
            ->where('slug', $roleSlug)
            ->whereNull('organization_id')
            ->first();

        if ($role === null) {
            return;
        }

        $role->permissions()->syncWithoutDetaching(
            $permissions->whereIn('slug', $slugs)->pluck('id'),
        );
    }
}