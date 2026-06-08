<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Database\Seeder;

class CompetitionsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect(['competitions'])->flatMap(function (string $module) {
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
            Permissions::slug('competitions', 'view'),
            Permissions::slug('competitions', 'create'),
            Permissions::slug('competitions', 'update'),
            Permissions::slug('competitions', 'delete'),
            Permissions::slug('competitions', 'manage'),
        ], $permissions);

        $this->attachToRole(Role::EVENT_ORGANIZER, [
            Permissions::slug('competitions', 'view'),
            Permissions::slug('competitions', 'manage'),
        ], $permissions);

        $this->attachToRole(Role::SPORTS_MANAGER, [
            Permissions::slug('competitions', 'view'),
            Permissions::slug('competitions', 'manage'),
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