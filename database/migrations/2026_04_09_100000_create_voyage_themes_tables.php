<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voyage_themes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('voyage_voyage_theme', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voyage_id')->constrained('voyages')->cascadeOnDelete();
            $table->foreignId('voyage_theme_id')->constrained('voyage_themes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['voyage_id', 'voyage_theme_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voyage_voyage_theme');
        Schema::dropIfExists('voyage_themes');
    }
};
