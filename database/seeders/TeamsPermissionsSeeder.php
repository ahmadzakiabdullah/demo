<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Database\Seeder;

class TeamsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect(['teams'])->flatMap(function (string $module) {
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

        $this->attachToRole(Role::ORG_ADMIN, [
            Permissions::slug('teams', 'view'),
            Permissions::slug('teams', 'create'),
            Permissions::slug('teams', 'update'),
            Permissions::slug('teams', 'delete'),
            Permissions::slug('teams', 'manage'),
        ], $permissions);

        $this->attachToRole(Role::EVENT_ORGANIZER, [
            Permissions::slug('teams', 'view'),
            Permissions::slug('teams', 'manage'),
        ], $permissions);

        $this->attachToRole(Role::SPORTS_MANAGER, [
            Permissions::slug('teams', 'view'),
        ], $permissions);

        $this->attachToRole(Role::TEAM_MANAGER, [
            Permissions::slug('teams', 'view'),
            Permissions::slug('teams', 'create'),
            Permissions::slug('teams', 'update'),
            Permissions::slug('teams', 'manage'),
        ], $permissions);

        $this->attachToRole(Role::ATHLETE, [
            Permissions::slug('teams', 'view'),
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