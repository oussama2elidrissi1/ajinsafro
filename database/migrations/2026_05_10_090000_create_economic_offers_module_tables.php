<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('economic_offers', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('internal_reference')->nullable()->index();
            $table->string('offer_type', 40)->index();
            $table->string('category', 40)->index();
            $table->string('status', 40)->default('draft')->index();
            $table->string('availability_status', 40)->default('available')->index();
            $table->string('main_image')->nullable();
            $table->string('fallback_image')->nullable();
            $table->string('video_url')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->decimal('price_from', 10, 2)->nullable();
            $table->decimal('old_price', 10, 2)->nullable();
            $table->string('currency', 10)->default('DH');
            $table->string('price_type', 40)->nullable();
            $table->decimal('deposit_amount', 10, 2)->nullable();
            $table->longText('payment_conditions')->nullable();
            $table->json('included_items')->nullable();
            $table->json('excluded_items')->nullable();
            $table->date('departure_date')->nullable();
            $table->date('return_date')->nullable();
            $table->unsignedInteger('duration_days')->nullable();
            $table->unsignedInteger('duration_nights')->nullable();
            $table->unsignedInteger('total_places')->default(0);
            $table->unsignedInteger('available_places')->default(0);
            $table->unsignedInteger('reserved_places')->default(0);
            $table->string('departure_city')->nullable()->index();
            $table->string('destination')->nullable()->index();
            $table->string('country')->nullable()->index();
            $table->string('arrival_city')->nullable();
            $table->string('address_zone')->nullable();
            $table->string('key_distance')->nullable();
            $table->boolean('transport_included')->default(false);
            $table->boolean('flight_included')->default(false);
            $table->boolean('hotel_included')->default(false);
            $table->boolean('meals_included')->default(false);
            $table->boolean('guide_included')->default(false);
            $table->boolean('insurance_included')->default(false);
            $table->boolean('transfer_included')->default(false);
            $table->string('accommodation_type')->nullable();
            $table->string('hotel_name')->nullable();
            $table->string('hotel_category')->nullable();
            $table->string('room_type')->nullable();
            $table->string('meal_plan', 40)->nullable();
            $table->text('program_summary')->nullable();
            $table->longText('cancellation_conditions')->nullable();
            $table->longText('required_documents')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('seo_image')->nullable();
            $table->json('seo_keywords')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'sort_order']);
        });

        Schema::create('economic_offer_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('economic_offers')->cascadeOnDelete();
            $table->string('image_path');
            $table->string('alt_text')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('economic_offer_departures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('economic_offers')->cascadeOnDelete();
            $table->date('departure_date');
            $table->date('return_date')->nullable();
            $table->decimal('price_from', 10, 2)->nullable();
            $table->unsignedInteger('total_places')->default(0);
            $table->unsignedInteger('available_places')->default(0);
            $table->unsignedInteger('reserved_places')->default(0);
            $table->string('status', 40)->default('published')->index();
            $table->text('internal_notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['offer_id', 'departure_date']);
            $table->index(['offer_id', 'status', 'departure_date']);
        });

        Schema::create('economic_offer_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('economic_offers')->cascadeOnDelete();
            $table->string('label');
            $table->string('type', 60)->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('old_price', 10, 2)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->string('condition')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['offer_id', 'type']);
        });

        Schema::create('economic_offer_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->nullable()->constrained('economic_offers')->nullOnDelete();
            $table->foreignId('departure_id')->nullable()->constrained('economic_offer_departures')->nullOnDelete();
            $table->string('offer_title')->nullable();
            $table->date('selected_departure_date')->nullable();
            $table->string('full_name');
            $table->string('phone', 60);
            $table->string('email');
            $table->unsignedInteger('adults')->default(1);
            $table->unsignedInteger('children')->default(0);
            $table->text('message')->nullable();
            $table->string('status', 40)->default('new')->index();
            $table->text('internal_notes')->nullable();
            $table->string('responsible_agent')->nullable();
            $table->string('source', 40)->default('wordpress');
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['offer_id', 'status']);
            $table->index(['email', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('economic_offer_requests');
        Schema::dropIfExists('economic_offer_prices');
        Schema::dropIfExists('economic_offer_departures');
        Schema::dropIfExists('economic_offer_images');
        Schema::dropIfExists('economic_offers');
    }
};
