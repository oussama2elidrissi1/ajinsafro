<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hôtels principaux
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('address')->nullable();
            $table->string('city', 120)->nullable();
            $table->string('country', 120)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('main_image_path', 255)->nullable();
            $table->unsignedTinyInteger('rating_average')->default(0)->comment('0-5');
            $table->unsignedInteger('reviews_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('amenities_summary')->nullable()->comment('Cache simple des principaux équipements');
            $table->timestamps();
        });

        // Images hôtel
        Schema::create('hotel_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->string('file_path', 255);
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        // Options / équipements (catalogue générique)
        Schema::create('hotel_amenities', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('label', 120);
            $table->string('icon', 80)->nullable()->comment('Classe d’icône front (ex: bx bx-wifi)');
            $table->timestamps();
        });

        // Pivot hôtel ↔ équipements
        Schema::create('hotel_amenity_hotel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->foreignId('hotel_amenity_id')->constrained('hotel_amenities')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['hotel_id', 'hotel_amenity_id']);
        });

        // Types de chambres
        Schema::create('hotel_room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('code', 50)->nullable();
            $table->unsignedTinyInteger('capacity_adults')->default(2);
            $table->unsignedTinyInteger('capacity_children')->default(0);
            $table->unsignedInteger('quantity')->default(0)->comment('Nombre de chambres de ce type');
            $table->decimal('base_price', 10, 2)->nullable();
            $table->string('currency', 10)->default('MAD');
            $table->text('description')->nullable();
            $table->json('amenities')->nullable()->comment('Options spécifiques à la chambre');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        // Reviews (évolutif)
        Schema::create('hotel_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating')->comment('1-5');
            $table->string('author_name', 120)->nullable();
            $table->text('comment')->nullable();
            $table->string('source', 50)->nullable()->comment('internal, booking, google, etc.');
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->index(['hotel_id', 'is_visible']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_reviews');
        Schema::dropIfExists('hotel_room_types');
        Schema::dropIfExists('hotel_amenity_hotel');
        Schema::dropIfExists('hotel_amenities');
        Schema::dropIfExists('hotel_images');
        Schema::dropIfExists('hotels');
    }
};
