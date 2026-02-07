<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voyage_flights', function (Blueprint $table) {
            $table->boolean('is_tentative')->default(false)->after('is_default');
            $table->string('cabin_baggage')->nullable()->after('baggage')->comment('e.g. 7 KGS');
            $table->string('checkin_baggage')->nullable()->after('cabin_baggage')->comment('e.g. 20 KGS');
        });
    }

    public function down(): void
    {
        Schema::table('voyage_flights', function (Blueprint $table) {
            $table->dropColumn(['is_tentative', 'cabin_baggage', 'checkin_baggage']);
        });
    }
};
