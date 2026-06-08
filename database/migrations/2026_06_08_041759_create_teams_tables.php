<?php

use Database\Seeders\TeamsPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sport_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->foreignId('coach_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('manager_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['event_id', 'sport_id', 'slug']);
            $table->index(['event_id', 'sport_id']);
            $table->index(['organization_id', 'name']);
        });

        Schema::create('team_athlete', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('member');
            $table->string('jersey_number', 10)->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'athlete_id']);
        });

        (new TeamsPermissionsSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('team_athlete');
        Schema::dropIfExists('teams');
    }
};