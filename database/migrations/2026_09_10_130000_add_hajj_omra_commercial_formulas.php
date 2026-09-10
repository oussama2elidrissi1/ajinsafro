<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hajj_omra_formulas', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('package_id')->constrained('hajj_omra_packages')->cascadeOnDelete();
            $table->foreignId('departure_id')->nullable()->constrained('hajj_omra_departures')->nullOnDelete();
            $table->string('name_fr')->nullable();
            $table->string('name_ar')->nullable();
            $table->text('description_fr')->nullable();
            $table->text('description_ar')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['package_id', 'is_active', 'sort_order'], 'ho_formulas_listing');
        });

        Schema::create('hajj_omra_formula_hotels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formula_id')->constrained('hajj_omra_formulas')->cascadeOnDelete();
            $table->foreignId('hotel_id')->constrained('hajj_omra_package_hotels')->cascadeOnDelete();
            $table->foreignId('program_day_id')->nullable()->constrained('hajj_omra_program_days')->nullOnDelete();
            // Only a formula-specific duration differs from the accommodation source.
            $table->unsignedInteger('nights_override')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unique(['formula_id', 'hotel_id'], 'ho_formula_hotel_unique');
        });

        Schema::create('hajj_omra_formula_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formula_id')->constrained('hajj_omra_formulas')->cascadeOnDelete();
            $table->foreignId('tariff_id')->constrained('hajj_omra_room_prices')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            // No copied amount: the linked tariff is the sole price source.
            $table->unique(['formula_id', 'tariff_id'], 'ho_formula_tariff_unique');
        });

        Schema::table('hajj_omra_booking_requests', function (Blueprint $table) {
            $table->foreignId('formula_id')->nullable()->constrained('hajj_omra_formulas')->nullOnDelete();
            $table->foreignId('tariff_id')->nullable()->constrained('hajj_omra_room_prices')->nullOnDelete();
        });

        Schema::table('hajj_omra_package_hotels', function (Blueprint $table) {
            $table->string('name_ar')->nullable();
            $table->string('location_ar')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('hajj_omra_booking_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('formula_id');
            $table->dropConstrainedForeignId('tariff_id');
        });
        Schema::dropIfExists('hajj_omra_formula_prices');
        Schema::dropIfExists('hajj_omra_formula_hotels');
        Schema::dropIfExists('hajj_omra_formulas');
        Schema::table('hajj_omra_package_hotels', fn (Blueprint $table) => $table->dropColumn(['name_ar', 'location_ar']));
    }
};
