<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->foreignId('event_participant_id')
                ->nullable()
                ->after('organization_id')
                ->constrained()
                ->nullOnDelete();
        });

        Schema::table('athletes', function (Blueprint $table) {
            $table->foreignId('event_participant_id')
                ->nullable()
                ->after('organization_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('event_participant_id');
        });

        Schema::table('athletes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('event_participant_id');
        });
    }
};