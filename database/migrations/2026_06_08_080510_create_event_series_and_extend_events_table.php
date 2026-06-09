<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('cadence', 20)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'slug']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->unsignedSmallInteger('edition_year')->default((int) date('Y'))->after('slug');
            $table->string('cadence', 20)->nullable()->after('edition_year');
            $table->foreignId('event_series_id')->nullable()->after('cadence')->constrained()->nullOnDelete();
            $table->string('participant_unit_label', 20)->nullable()->after('event_series_id');
            $table->index(['organization_id', 'edition_year']);
        });

        foreach (DB::table('events')->select(['id', 'starts_at', 'created_at'])->get() as $event) {
            $year = $event->starts_at !== null
                ? (int) date('Y', strtotime((string) $event->starts_at))
                : (int) date('Y', strtotime((string) $event->created_at));

            DB::table('events')->where('id', $event->id)->update(['edition_year' => $year]);
        }
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['event_series_id']);
            $table->dropIndex(['organization_id', 'edition_year']);
            $table->dropColumn([
                'edition_year',
                'cadence',
                'event_series_id',
                'participant_unit_label',
            ]);
        });

        Schema::dropIfExists('event_series');
    }
};