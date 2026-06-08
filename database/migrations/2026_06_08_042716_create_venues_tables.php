<?php

use Database\Seeders\VenuesPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('address')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('timezone')->default('UTC');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'slug']);
            $table->index(['organization_id', 'name']);
        });

        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('type', 20);
            $table->unsignedInteger('capacity')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['venue_id', 'slug']);
            $table->index(['venue_id', 'sort_order']);
        });

        Schema::create('event_venue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'venue_id']);
            $table->index(['event_id', 'is_primary']);
        });

        Schema::create('event_sport_venue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sport_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['event_id', 'sport_id', 'venue_id']);
            $table->index(['event_id', 'venue_id']);
        });

        (new VenuesPermissionsSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('event_sport_venue');
        Schema::dropIfExists('event_venue');
        Schema::dropIfExists('facilities');
        Schema::dropIfExists('venues');
    }
};