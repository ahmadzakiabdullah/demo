<?php

use App\Models\Role;
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
        $systemOwnerRoleId = Role::query()
            ->where('slug', Role::SYSTEM_OWNER)
            ->whereNull('organization_id')
            ->value('id');

        if ($systemOwnerRoleId) {
            $adminUserIds = DB::table('users')
                ->where('role', 'admin')
                ->pluck('id');

            foreach ($adminUserIds as $userId) {
                DB::table('role_user')->updateOrInsert(
                    [
                        'role_id' => $systemOwnerRoleId,
                        'user_id' => $userId,
                    ],
                );
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('user')->after('email');
        });

        $systemOwnerRoleId = Role::query()
            ->where('slug', Role::SYSTEM_OWNER)
            ->whereNull('organization_id')
            ->value('id');

        $ownerUserIds = collect();

        if ($systemOwnerRoleId) {
            $ownerUserIds = DB::table('role_user')
                ->where('role_id', $systemOwnerRoleId)
                ->pluck('user_id');

            DB::table('users')
                ->whereIn('id', $ownerUserIds)
                ->update(['role' => 'admin']);
        }

        DB::table('users')
            ->whereNotIn('id', $ownerUserIds)
            ->update(['role' => 'user']);
    }
};