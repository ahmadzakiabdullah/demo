<?php

use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        (new RolesAndPermissionsSeeder)->run();

        Schema::table('organization_user', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
        });

        $roleMap = Role::query()
            ->whereNull('organization_id')
            ->pluck('id', 'slug');

        foreach (DB::table('organization_user')->get() as $row) {
            $slug = match ($row->role) {
                'org_admin' => Role::ORG_ADMIN,
                'member' => Role::ATHLETE,
                default => Role::ATHLETE,
            };

            DB::table('organization_user')
                ->where('organization_id', $row->organization_id)
                ->where('user_id', $row->user_id)
                ->update(['role_id' => $roleMap[$slug]]);
        }

        Schema::table('organization_user', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organization_user', function (Blueprint $table) {
            $table->string('role', 50)->default('member')->after('user_id');
        });

        $roleMap = Role::query()
            ->whereNull('organization_id')
            ->pluck('slug', 'id');

        foreach (DB::table('organization_user')->get() as $row) {
            $legacyRole = match ($roleMap[$row->role_id] ?? null) {
                Role::ORG_ADMIN => 'org_admin',
                default => 'member',
            };

            DB::table('organization_user')
                ->where('organization_id', $row->organization_id)
                ->where('user_id', $row->user_id)
                ->update(['role' => $legacyRole]);
        }

        Schema::table('organization_user', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });
    }
};