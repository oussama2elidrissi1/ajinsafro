<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accommodation_packages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('country')->default('Maroc');
            $table->string('city');
            $table->unsignedInteger('duration_days');
            $table->unsignedInteger('nights');
            $table->string('pension_type')->nullable();
            $table->string('accommodation_type')->nullable();
            $table->string('badge')->nullable();
            $table->text('short_description')->nullable();
            $table->json('includes')->nullable();
            $table->string('image_url')->nullable();
            $table->decimal('price_from', 10, 2);
            $table->string('currency')->default('MAD');
            $table->boolean('is_featured')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index(['country', 'city']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accommodation_packages');
    }
};
