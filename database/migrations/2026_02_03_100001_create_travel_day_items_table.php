<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_day_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voyage_id')->constrained('voyages')->onDelete('cascade');
            $table->unsignedInteger('day_number');
            $table->unsignedInteger('start_day');
            $table->unsignedInteger('end_day')->nullable();
            $table->unsignedInteger('nights')->default(0);
            $table->string('type', 50); // flight|hotel_stay|transfer|activity|meal|addon
            $table->string('title');
            $table->text('details')->nullable();
            $table->boolean('included')->default(true);
            $table->integer('price_delta_per_person')->default(0)->comment('Price in cents, 0 if included');
            $table->longText('options_json')->nullable()->comment('JSON for alternative/modify options');
            $table->longText('meta_json')->nullable()->comment('Extra metadata: supplier_id, time, etc.');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['voyage_id', 'day_number']);
            $table->index(['voyage_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_day_items');
    }
};
