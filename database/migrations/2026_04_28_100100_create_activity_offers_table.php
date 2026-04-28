<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_offers', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('country');
            $table->string('city');
            $table->string('category');
            $table->string('duration_label')->nullable();
            $table->string('badge')->nullable();
            $table->text('short_description')->nullable();
            $table->json('includes')->nullable();
            $table->string('image_url')->nullable();
            $table->decimal('price_from', 10, 2);
            $table->string('currency')->default('MAD');
            $table->string('availability_label')->default('Disponible');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index(['country', 'city']);
            $table->index(['category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_offers');
    }
};
