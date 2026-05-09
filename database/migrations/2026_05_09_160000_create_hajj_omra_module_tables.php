<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hajj_omra_packages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('type', 40)->index();
            $table->string('status', 40)->default('draft')->index();
            $table->string('main_image')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->string('departure_city')->nullable()->index();
            $table->string('destination')->nullable()->index();
            $table->unsignedInteger('duration_days')->nullable();
            $table->unsignedInteger('duration_nights')->nullable();
            $table->date('start_date')->nullable();
            $table->date('return_date')->nullable();
            $table->decimal('adult_price', 10, 2)->nullable();
            $table->decimal('child_price', 10, 2)->nullable();
            $table->decimal('baby_price', 10, 2)->nullable();
            $table->string('currency', 10)->default('DH');
            $table->unsignedInteger('available_places')->default(0);
            $table->unsignedInteger('reserved_places')->default(0);
            $table->string('makkah_hotel')->nullable();
            $table->string('makkah_haram_distance')->nullable();
            $table->string('madinah_hotel')->nullable();
            $table->string('madinah_haram_distance')->nullable();
            $table->string('room_type', 40)->nullable();
            $table->boolean('transport_included')->default(false);
            $table->boolean('visa_included')->default(false);
            $table->boolean('guidance_included')->default(false);
            $table->string('meal_plan', 40)->nullable();
            $table->json('included_items')->nullable();
            $table->json('excluded_items')->nullable();
            $table->longText('booking_conditions')->nullable();
            $table->longText('required_documents')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_featured')->default(false)->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'sort_order']);
        });

        Schema::create('hajj_omra_package_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('hajj_omra_packages')->cascadeOnDelete();
            $table->string('image_path');
            $table->string('alt_text')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('hajj_omra_departures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('hajj_omra_packages')->cascadeOnDelete();
            $table->date('departure_date');
            $table->date('return_date')->nullable();
            $table->string('status', 40)->default('published')->index();
            $table->unsignedInteger('available_places')->default(0);
            $table->unsignedInteger('reserved_places')->default(0);
            $table->decimal('price_from', 10, 2)->nullable();
            $table->text('internal_notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['package_id', 'departure_date']);
            $table->index(['package_id', 'status', 'departure_date']);
        });

        Schema::create('hajj_omra_room_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('hajj_omra_packages')->cascadeOnDelete();
            $table->string('room_type', 40);
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('stock')->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['package_id', 'room_type']);
        });

        Schema::create('hajj_omra_program_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('hajj_omra_packages')->cascadeOnDelete();
            $table->unsignedInteger('day_number');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('city')->nullable();
            $table->string('image_path')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['package_id', 'day_number']);
        });

        Schema::create('hajj_omra_booking_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->nullable()->constrained('hajj_omra_packages')->nullOnDelete();
            $table->foreignId('departure_id')->nullable()->constrained('hajj_omra_departures')->nullOnDelete();
            $table->string('package_title')->nullable();
            $table->date('selected_departure_date')->nullable();
            $table->string('full_name');
            $table->string('phone', 60);
            $table->string('email');
            $table->unsignedInteger('adults')->default(1);
            $table->unsignedInteger('children')->default(0);
            $table->string('room_type', 40)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 40)->default('new')->index();
            $table->text('internal_notes')->nullable();
            $table->string('source', 40)->default('wordpress');
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['package_id', 'status']);
            $table->index(['email', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hajj_omra_booking_requests');
        Schema::dropIfExists('hajj_omra_program_days');
        Schema::dropIfExists('hajj_omra_room_prices');
        Schema::dropIfExists('hajj_omra_departures');
        Schema::dropIfExists('hajj_omra_package_images');
        Schema::dropIfExists('hajj_omra_packages');
    }
};
