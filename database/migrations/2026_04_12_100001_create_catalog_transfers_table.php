<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('catalog_transfers')) {
            return;
        }

        Schema::create('catalog_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wp_post_id')->nullable()->unique();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('cars_address')->nullable();
            $table->decimal('cars_price', 10, 2)->nullable();
            $table->decimal('min_price', 10, 2)->nullable();
            $table->decimal('max_price', 10, 2)->nullable();
            $table->unsignedInteger('number_car')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->string('transfer_from')->nullable();
            $table->string('transfer_to')->nullable();
            $table->string('transfer_type', 120)->nullable();
            $table->unsignedInteger('transfer_capacity')->nullable();
            $table->string('transfer_vehicle_type', 120)->nullable();
            $table->unsignedBigInteger('featured_image_wp_id')->nullable();
            $table->timestamp('wp_synced_at')->nullable();
            $table->string('wp_sync_hash', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_transfers');
    }
};
