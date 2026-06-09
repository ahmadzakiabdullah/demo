<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('medals', function (Blueprint $table) {
            $table->foreignId('event_participant_id')
                ->nullable()
                ->after('competition_id')
                ->constrained('event_participants')
                ->nullOnDelete();

            $table->index(['event_id', 'event_participant_id', 'type']);
        });

        Schema::table('rankings', function (Blueprint $table) {
            $table->foreignId('event_participant_id')
                ->nullable()
                ->after('competition_id')
                ->constrained('event_participants')
                ->nullOnDelete();

            $table->index(['competition_id', 'event_participant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rankings', function (Blueprint $table) {
            $table->dropIndex(['competition_id', 'event_participant_id']);
            $table->dropConstrainedForeignId('event_participant_id');
        });

        Schema::table('medals', function (Blueprint $table) {
            $table->dropIndex(['event_id', 'event_participant_id', 'type']);
            $table->dropConstrainedForeignId('event_participant_id');
        });
    }
};
