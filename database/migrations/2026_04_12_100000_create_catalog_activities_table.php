<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('catalog_activities')) {
            return;
        }

        Schema::create('catalog_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wp_post_id')->nullable()->unique();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('address')->nullable();
            $table->string('type_activity', 120)->nullable();
            $table->decimal('adult_price', 10, 2)->nullable();
            $table->decimal('child_price', 10, 2)->nullable();
            $table->decimal('min_price', 10, 2)->nullable();
            $table->string('duration', 120)->nullable();
            $table->unsignedInteger('max_people')->nullable();
            $table->decimal('rate_review', 3, 1)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->string('category', 120)->nullable();
            $table->string('place_text')->nullable();
            $table->unsignedInteger('min_age')->nullable();
            $table->unsignedInteger('max_age')->nullable();
            $table->unsignedBigInteger('featured_image_wp_id')->nullable();
            $table->json('gallery_image_wp_ids')->nullable();
            $table->timestamp('wp_synced_at')->nullable();
            $table->string('wp_sync_hash', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_activities');
    }
};
