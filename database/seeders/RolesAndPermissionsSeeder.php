<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Seed system roles and module permissions.
     */
    public function run(): void
    {
        $permissions = collect(Permissions::all())->map(function (string $slug) {
            [$module, $action] = explode('.', $slug, 2);

            return Permission::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => ucfirst($action).' '.str_replace('_', ' ', $module),
                    'module' => $module,
                ],
            );
        });

        $systemOwner = $this->createSystemRole(
            Role::SYSTEM_OWNER,
            'System Owner',
            'Full platform access across all organizations.',
        );
        $systemOwner->permissions()->sync($permissions->pluck('id'));

        $orgAdmin = $this->createSystemRole(
            Role::ORG_ADMIN,
            'Organization Administrator',
            'Manage users, branches, and events within an organization.',
        );
        $orgAdmin->permissions()->sync(
            $permissions->whereIn('slug', [
                Permissions::slug('organizations', 'view'),
                Permissions::slug('organizations', 'update'),
                Permissions::slug('branches', 'view'),
                Permissions::slug('branches', 'create'),
                Permissions::slug('branches', 'update'),
                Permissions::slug('branches', 'delete'),
                Permissions::slug('branches', 'manage'),
                Permissions::slug('users', 'view'),
                Permissions::slug('users', 'create'),
                Permissions::slug('users', 'update'),
                Permissions::slug('users', 'delete'),
                Permissions::slug('users', 'manage'),
                Permissions::slug('roles', 'view'),
                Permissions::slug('events', 'view'),
                Permissions::slug('events', 'create'),
                Permissions::slug('events', 'update'),
                Permissions::slug('events', 'delete'),
                Permissions::slug('events', 'manage'),
                Permissions::slug('sports', 'view'),
                Permissions::slug('sports', 'create'),
                Permissions::slug('sports', 'update'),
                Permissions::slug('sports', 'delete'),
                Permissions::slug('sports', 'manage'),
                Permissions::slug('audit_logs', 'view'),
            ])->pluck('id'),
        );

        $this->createSystemRole(
            Role::EVENT_ORGANIZER,
            'Event Organizer',
            'Manage assigned events, schedules, and participants.',
        )->permissions()->sync(
            $permissions->whereIn('slug', [
                Permissions::slug('events', 'view'),
                Permissions::slug('events', 'create'),
                Permissions::slug('events', 'update'),
                Permissions::slug('events', 'manage'),
                Permissions::slug('sports', 'view'),
                Permissions::slug('users', 'view'),
                Permissions::slug('branches', 'view'),
            ])->pluck('id'),
        );

        $this->createSystemRole(
            Role::SPORTS_MANAGER,
            'Sports Manager',
            'Configure sports, disciplines, and categories.',
        )->permissions()->sync(
            $permissions->whereIn('slug', [
                Permissions::slug('events', 'view'),
                Permissions::slug('sports', 'view'),
                Permissions::slug('sports', 'create'),
                Permissions::slug('sports', 'update'),
                Permissions::slug('sports', 'delete'),
                Permissions::slug('sports', 'manage'),
                Permissions::slug('users', 'view'),
            ])->pluck('id'),
        );

        $this->createSystemRole(
            Role::TEAM_MANAGER,
            'Team Manager',
            'Manage team registration and lineups.',
        )->permissions()->sync(
            $permissions->whereIn('slug', [
                Permissions::slug('events', 'view'),
                Permissions::slug('users', 'view'),
            ])->pluck('id'),
        );

        $this->createSystemRole(
            Role::ATHLETE,
            'Athlete',
            'Self-service profile and registration.',
        )->permissions()->sync(
            $permissions->whereIn('slug', [
                Permissions::slug('events', 'view'),
                Permissions::slug('users', 'view'),
            ])->pluck('id'),
        );

        $this->createSystemRole(
            Role::OFFICIAL,
            'Official',
            'Score entry and match officiating.',
        )->permissions()->sync(
            $permissions->whereIn('slug', [
                Permissions::slug('events', 'view'),
            ])->pluck('id'),
        );

        $this->createSystemRole(
            Role::VOLUNTEER,
            'Volunteer',
            'Event operations support.',
        )->permissions()->sync(
            $permissions->whereIn('slug', [
                Permissions::slug('events', 'view'),
            ])->pluck('id'),
        );

        $this->createSystemRole(
            Role::MEDIA,
            'Media',
            'Read event data and upload media assets.',
        )->permissions()->sync(
            $permissions->whereIn('slug', [
                Permissions::slug('events', 'view'),
            ])->pluck('id'),
        );
    }

    private function createSystemRole(string $slug, string $name, string $description): Role
    {
        return Role::query()->firstOrCreate(
            ['slug' => $slug, 'organization_id' => null],
            [
                'name' => $name,
                'description' => $description,
            ],
        );
    }
}