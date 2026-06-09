<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(EventReferenceDataSeeder::class);

        User::factory()->admin()->create([
            'name' => 'Test Admin',
            'email' => 'test@example.com',
        ]);

        // Tambah pengguna Ahmad Zaki sebagai Admin
        User::factory()->admin()->create([
            'name' => 'Ahmad Zaki',
            'email' => 'ahmadzaki@utem.edu.my',
        ]);

        $this->call(OrganizationSeeder::class);
    }
}