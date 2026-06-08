<?php

use Database\Seeders\AthletesPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('athletes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->date('dob')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('nationality', 100)->nullable();
            $table->string('id_number', 100)->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('medical_clearance')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'name']);
            $table->unique(['organization_id', 'id_number']);
        });

        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sport_id')->constrained()->cascadeOnDelete();
            $table->morphs('registrable');
            $table->foreignId('sport_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sport_division_id')->nullable()->constrained('sport_divisions')->nullOnDelete();
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['event_id', 'sport_id', 'registrable_type', 'registrable_id'],
                'registrations_event_sport_registrable_unique',
            );
            $table->index(['event_id', 'status']);
            $table->index(['event_id', 'sport_id']);
        });

        (new AthletesPermissionsSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
        Schema::dropIfExists('athletes');
    }
};