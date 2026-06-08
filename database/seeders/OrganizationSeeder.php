<?php

namespace Database\Seeders;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    /**
     * Seed the pilot organization and link the project owner.
     */
    public function run(): void
    {
        $organization = Organization::query()->firstOrCreate(
            ['slug' => 'utem'],
            [
                'name' => 'Universiti Teknikal Malaysia Melaka',
                'type' => OrganizationType::University,
                'timezone' => 'Asia/Kuala_Lumpur',
                'locale' => 'en',
                'status' => OrganizationStatus::Active,
            ],
        );

        $orgAdminRoleId = Role::query()
            ->where('slug', Role::ORG_ADMIN)
            ->whereNull('organization_id')
            ->value('id');

        $owner = User::query()->where('email', 'ahmadzaki@utem.edu.my')->first();

        if ($owner && $orgAdminRoleId && ! $owner->organizations()->where('organizations.id', $organization->id)->exists()) {
            $owner->organizations()->attach($organization->id, [
                'role_id' => $orgAdminRoleId,
                'status' => 'active',
            ]);
        }
    }
}