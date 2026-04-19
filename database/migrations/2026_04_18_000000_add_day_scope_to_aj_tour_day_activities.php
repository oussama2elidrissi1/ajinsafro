<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection()
    {
        return 'wp';
    }

    public function up(): void
    {
        Schema::connection('wp')->table('aj_tour_day_activities', function (Blueprint $table) {
            $table->enum('day_scope', ['fixed', 'open'])->default('fixed')->after('is_included');
        });
    }

    public function down(): void
    {
        Schema::connection('wp')->table('aj_tour_day_activities', function (Blueprint $table) {
            $table->dropColumn('day_scope');
        });
    }
};
