<?php

use Database\Seeders\CompetitionFormatsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->json('settings')->nullable()->after('notes');
        });

        Schema::table('sports', function (Blueprint $table) {
            $table->json('score_schema')->nullable()->after('rules');
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->foreignId('loser_advances_to_match_id')
                ->nullable()
                ->after('winner_advances_side')
                ->constrained('matches')
                ->nullOnDelete();
            $table->string('loser_advances_side', 10)->nullable()->after('loser_advances_to_match_id');
            $table->string('bracket_lane', 20)->nullable()->after('loser_advances_side');
        });

        (new CompetitionFormatsSeeder)->run();
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropForeign(['loser_advances_to_match_id']);
            $table->dropColumn(['loser_advances_to_match_id', 'loser_advances_side', 'bracket_lane']);
        });

        Schema::table('sports', function (Blueprint $table) {
            $table->dropColumn('score_schema');
        });

        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn('settings');
        });
    }
};