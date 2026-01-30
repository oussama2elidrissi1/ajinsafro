<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_program_days', function (Blueprint $table) {
            $table->string('day_label', 20)->nullable()->after('city'); // inclus, optionnel, libre
            $table->json('meals_json')->nullable()->after('day_label'); // ["breakfast","lunch","dinner"]
            $table->longText('content_html')->nullable()->after('meals_json');
        });
    }

    public function down(): void
    {
        Schema::table('travel_program_days', function (Blueprint $table) {
            $table->dropColumn(['day_label', 'meals_json', 'content_html']);
        });
    }
};
