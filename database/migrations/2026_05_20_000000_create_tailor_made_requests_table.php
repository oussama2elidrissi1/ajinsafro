<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tailor_made_requests', function (Blueprint $table) {
            $table->id();

            $table->string('type', 50)->default('demande_a_la_carte');
            $table->string('source', 50)->default('public_st_tour');
            $table->string('status', 30)->default('new');

            $table->unsignedBigInteger('voyage_id')->nullable()->index();
            $table->unsignedBigInteger('wp_post_id')->nullable()->index();

            $table->string('tour_title')->nullable();
            $table->text('tour_url')->nullable();
            $table->text('booking_url')->nullable();

            $table->string('custom_departure_place')->nullable();
            $table->date('custom_departure_date')->nullable()->index();

            $table->unsignedInteger('adults')->default(1);
            $table->unsignedInteger('children')->default(0);
            $table->unsignedInteger('travellers_total')->default(1);

            $table->string('price_currency', 10)->nullable();
            $table->decimal('price_per_person', 12, 2)->nullable();
            $table->decimal('price_total', 12, 2)->nullable();

            $table->string('client_first_name')->nullable();
            $table->string('client_last_name')->nullable();
            $table->string('client_phone')->nullable();
            $table->string('client_email')->nullable();

            $table->text('message')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tailor_made_requests');
    }
};

