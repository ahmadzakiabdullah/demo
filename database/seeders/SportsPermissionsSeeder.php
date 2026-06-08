<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Database\Seeder;

class SportsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect(['sports'])->flatMap(function (string $module) {
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

        $systemOwner = Role::query()
            ->where('slug', Role::SYSTEM_OWNER)
            ->whereNull('organization_id')
            ->first();

        if ($systemOwner) {
            $systemOwner->permissions()->syncWithoutDetaching($permissions->pluck('id'));
        }

        $orgAdmin = Role::query()
            ->where('slug', Role::ORG_ADMIN)
            ->whereNull('organization_id')
            ->first();

        if ($orgAdmin) {
            $orgAdmin->permissions()->syncWithoutDetaching(
                $permissions->whereIn('slug', [
                    Permissions::slug('sports', 'view'),
                    Permissions::slug('sports', 'create'),
                    Permissions::slug('sports', 'update'),
                    Permissions::slug('sports', 'delete'),
                    Permissions::slug('sports', 'manage'),
                ])->pluck('id'),
            );
        }

        $sportsManager = Role::query()
            ->where('slug', Role::SPORTS_MANAGER)
            ->whereNull('organization_id')
            ->first();

        if ($sportsManager) {
            $sportsManager->permissions()->syncWithoutDetaching(
                $permissions->whereIn('slug', [
                    Permissions::slug('sports', 'view'),
                    Permissions::slug('sports', 'create'),
                    Permissions::slug('sports', 'update'),
                    Permissions::slug('sports', 'delete'),
                    Permissions::slug('sports', 'manage'),
                ])->pluck('id'),
            );
        }

        $eventOrganizer = Role::query()
            ->where('slug', Role::EVENT_ORGANIZER)
            ->whereNull('organization_id')
            ->first();

        if ($eventOrganizer) {
            $eventOrganizer->permissions()->syncWithoutDetaching(
                $permissions->whereIn('slug', [
                    Permissions::slug('sports', 'view'),
                ])->pluck('id'),
            );
        }
    }
}