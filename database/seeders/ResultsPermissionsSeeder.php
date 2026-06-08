<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Database\Seeder;

class ResultsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect(['results'])->flatMap(function (string $module) {
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
            Permissions::slug('results', 'view'),
            Permissions::slug('results', 'create'),
            Permissions::slug('results', 'update'),
            Permissions::slug('results', 'manage'),
        ], $permissions);

        $this->attachToRole(Role::EVENT_ORGANIZER, [
            Permissions::slug('results', 'view'),
            Permissions::slug('results', 'manage'),
        ], $permissions);

        $this->attachToRole(Role::SPORTS_MANAGER, [
            Permissions::slug('results', 'view'),
            Permissions::slug('results', 'manage'),
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