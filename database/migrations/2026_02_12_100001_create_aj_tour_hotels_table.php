<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un seul hôtel principal par tour. Check-in = Jour 1, check-out = Dernier jour.
     * Run on WP connection.
     */
    public function up(): void
    {
        $connection = 'wp';
        $schema = Schema::connection($connection);

        if ($schema->hasTable('aj_tour_hotels')) {
            return;
        }

        $schema->create('aj_tour_hotels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tour_id')->comment('wp_posts.ID');
            $table->string('hotel_name')->nullable();
            $table->unsignedTinyInteger('stars')->nullable();
            $table->string('address')->nullable();
            $table->string('room_type', 200)->nullable();
            $table->string('meal_plan', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique('tour_id');
            $table->index('tour_id');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('wp');
        if ($schema->hasTable('aj_tour_hotels')) {
            $schema->dropIfExists('aj_tour_hotels');
        }
    }
};
