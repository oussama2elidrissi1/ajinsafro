<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run on WP connection (e.g. cFdgeZ_aj_airlines).
     */
    public function up(): void
    {
        if (Schema::connection('wp')->hasTable('aj_airlines')) {
            return;
        }

        Schema::connection('wp')->create('aj_airlines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('iata_code', 10)->nullable();
            $table->string('logo_url')->nullable();
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('wp')->dropIfExists('aj_airlines');
    }
};
