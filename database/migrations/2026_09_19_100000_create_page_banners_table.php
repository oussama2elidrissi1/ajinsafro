<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('page_banners')) {
            return;
        }

        Schema::create('page_banners', function (Blueprint $table) {
            $table->id();
            // Une banniere par page publique : 'voyages', puis d'autres au besoin.
            $table->string('page_key', 60)->unique();
            $table->string('image_path')->nullable();
            $table->string('alt_text')->nullable();
            $table->string('link_url', 2048)->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_banners');
    }
};
