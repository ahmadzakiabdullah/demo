<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->string('name');
            $table->string('code', 20)->nullable();
            $table->string('status', 20)->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['event_id', 'type']);
            $table->index(['organization_id', 'event_id']);
            $table->unique(['event_id', 'code']);
        });

        Schema::create('participant_sport_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_participant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sport_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sport_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sport_division_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['event_participant_id', 'sport_id', 'sport_category_id', 'sport_division_id'],
                'participant_sport_entries_unique',
            );
        });

        (new \Database\Seeders\EventParticipantsPermissionsSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('participant_sport_entries');
        Schema::dropIfExists('event_participants');
    }
};