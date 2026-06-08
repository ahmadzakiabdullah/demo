<?php

use Database\Seeders\ResultsPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->foreignId('winner_advances_to_match_id')
                ->nullable()
                ->after('notes')
                ->constrained('matches')
                ->nullOnDelete();
            $table->string('winner_advances_side', 10)->nullable()->after('winner_advances_to_match_id');
        });

        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('data');
            $table->string('status', 20)->default('pending');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('match_id');
            $table->index(['status', 'confirmed_at']);
        });

        Schema::create('rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->string('rankable_type');
            $table->unsignedBigInteger('rankable_id');
            $table->unsignedSmallInteger('position')->default(0);
            $table->unsignedSmallInteger('points')->default(0);
            $table->unsignedSmallInteger('played')->default(0);
            $table->unsignedSmallInteger('won')->default(0);
            $table->unsignedSmallInteger('drawn')->default(0);
            $table->unsignedSmallInteger('lost')->default(0);
            $table->unsignedSmallInteger('scored_for')->default(0);
            $table->unsignedSmallInteger('scored_against')->default(0);
            $table->timestamps();

            $table->unique(['competition_id', 'rankable_type', 'rankable_id']);
            $table->index(['competition_id', 'position']);
        });

        Schema::create('medals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sport_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competition_id')->nullable()->constrained()->nullOnDelete();
            $table->string('medalable_type');
            $table->unsignedBigInteger('medalable_id');
            $table->string('type', 10);
            $table->timestamps();

            $table->unique(['competition_id', 'medalable_type', 'medalable_id', 'type']);
            $table->index(['event_id', 'sport_id', 'type']);
        });

        (new ResultsPermissionsSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('medals');
        Schema::dropIfExists('rankings');
        Schema::dropIfExists('results');

        Schema::table('matches', function (Blueprint $table) {
            $table->dropForeign(['winner_advances_to_match_id']);
            $table->dropColumn(['winner_advances_to_match_id', 'winner_advances_side']);
        });
    }
};