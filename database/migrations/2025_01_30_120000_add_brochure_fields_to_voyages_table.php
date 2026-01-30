<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            $table->text('accroche')->nullable()->after('description');
            $table->string('destination')->nullable()->after('accroche');
            $table->string('duration_text')->nullable()->after('destination');
            $table->unsignedInteger('price_from')->nullable()->after('duration_text');
            $table->unsignedInteger('old_price')->nullable()->after('price_from');
            $table->string('currency', 10)->default('MAD')->after('old_price');
            $table->unsignedSmallInteger('min_people')->nullable()->after('currency');
            $table->text('departure_policy')->nullable()->after('min_people');
            $table->string('status', 20)->default('actif')->after('departure_policy');
        });
    }

    public function down(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            $table->dropColumn([
                'accroche', 'destination', 'duration_text', 'price_from', 'old_price',
                'currency', 'min_people', 'departure_policy', 'status'
            ]);
        });
    }
};
