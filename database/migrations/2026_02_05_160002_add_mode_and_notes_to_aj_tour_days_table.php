<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add mode (free/program), day_title, notes to existing aj_tour_days.
     */
    public function up(): void
    {
        if (!Schema::connection('wp')->hasTable('aj_tour_days')) {
            return;
        }

        Schema::connection('wp')->table('aj_tour_days', function (Blueprint $table) {
            if (!Schema::connection('wp')->hasColumn('aj_tour_days', 'mode')) {
                $table->string('mode', 20)->default('program')->after('description');
            }
            if (!Schema::connection('wp')->hasColumn('aj_tour_days', 'day_title')) {
                $table->string('day_title')->nullable()->after('mode');
            }
            if (!Schema::connection('wp')->hasColumn('aj_tour_days', 'notes')) {
                $table->longText('notes')->nullable()->after('day_title');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::connection('wp')->hasTable('aj_tour_days')) {
            return;
        }

        $schema = Schema::connection('wp');
        $drops = array_filter(['mode', 'day_title', 'notes'], fn ($col) => $schema->hasColumn('aj_tour_days', $col));
        if (!empty($drops)) {
            Schema::connection('wp')->table('aj_tour_days', function (Blueprint $table) use ($drops) {
                $table->dropColumn($drops);
            });
        }
    }
};
