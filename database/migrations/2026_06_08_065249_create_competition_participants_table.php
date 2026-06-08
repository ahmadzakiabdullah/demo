<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->string('participant_type');
            $table->unsignedBigInteger('participant_id');
            $table->unsignedSmallInteger('seed')->default(0);
            $table->unsignedSmallInteger('ladder_rank')->default(0);
            $table->decimal('swiss_points', 5, 1)->default(0);
            $table->unsignedSmallInteger('swiss_buchholz')->default(0);
            $table->timestamps();

            $table->unique(['competition_id', 'participant_type', 'participant_id'], 'competition_participant_unique');
            $table->index(['competition_id', 'seed']);
            $table->index(['competition_id', 'ladder_rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_participants');
    }
};