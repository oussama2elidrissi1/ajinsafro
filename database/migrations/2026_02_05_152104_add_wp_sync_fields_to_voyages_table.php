<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            // WP sync state fields
            $table->timestamp('wp_last_modified_gmt_cache')->nullable()->after('wp_sync_hash');
            
            // Additional Traveler meta fields
            $table->integer('max_people')->nullable()->after('min_people');
            $table->string('tour_price_by', 50)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->text('st_google_map')->nullable();
            $table->string('multi_location', 50)->nullable();
            $table->string('discount_by_people_type', 50)->nullable();
            $table->string('discount_type', 50)->nullable();
            $table->string('calculator_discount_by_people_type', 50)->nullable();
            $table->boolean('hide_adult_in_booking_form')->default(false);
            $table->string('st_tour_external_booking')->nullable();
            
            // Tour content fields (stored as JSON for flexibility)
            $table->json('tours_include')->nullable();
            $table->json('tours_exclude')->nullable();
            $table->json('tours_highlight')->nullable();
            $table->string('tours_program_style', 50)->nullable();
            
            // Payment gateway metas (JSON key-value store)
            $table->json('payment_gateway_metas')->nullable();
            
            // Gallery image IDs cache (comma-separated WP attachment IDs)
            $table->text('gallery_wp_ids')->nullable();
            
            $table->index('max_people');
            $table->index('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            $table->dropColumn([
                'wp_last_modified_gmt_cache',
                'max_people',
                'tour_price_by',
                'is_featured',
                'st_google_map',
                'multi_location',
                'discount_by_people_type',
                'discount_type',
                'calculator_discount_by_people_type',
                'hide_adult_in_booking_form',
                'st_tour_external_booking',
                'tours_include',
                'tours_exclude',
                'tours_highlight',
                'tours_program_style',
                'payment_gateway_metas',
                'gallery_wp_ids',
            ]);
        });
    }
};
