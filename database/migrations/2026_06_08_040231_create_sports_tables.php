<?php

use Database\Seeders\SportsPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('template_slug')->nullable();
            $table->string('status', 20)->default('active');
            $table->json('rules')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['event_id', 'slug']);
            $table->index(['event_id', 'status']);
        });

        Schema::create('sport_disciplines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['sport_id', 'slug']);
        });

        Schema::create('sport_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_discipline_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('gender', 20)->default('open');
            $table->unsignedTinyInteger('min_age')->nullable();
            $table->unsignedTinyInteger('max_age')->nullable();
            $table->decimal('min_weight', 5, 2)->nullable();
            $table->decimal('max_weight', 5, 2)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['sport_discipline_id', 'slug']);
        });

        Schema::create('sport_divisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['sport_category_id', 'slug']);
        });

        (new SportsPermissionsSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('sport_divisions');
        Schema::dropIfExists('sport_categories');
        Schema::dropIfExists('sport_disciplines');
        Schema::dropIfExists('sports');
    }
};