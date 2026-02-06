<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations on WP connection (cFdgeZ_aj_tour_activity_selections).
     * Client add/remove optional activities; admin data in aj_tour_day_activities is never modified.
     */
    public function up(): void
    {
        Schema::connection('wp')->create('aj_tour_activity_selections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tour_id')->comment('wp_posts.ID');
            $table->unsignedBigInteger('day_id')->comment('aj_tour_days.id');
            $table->unsignedBigInteger('activity_id')->comment('aj_activities.id');
            $table->unsignedBigInteger('source_day_activity_id')->nullable()->comment('aj_tour_day_activities.id if from admin program');
            $table->enum('action', ['added', 'removed']);
            $table->string('session_token', 64)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamps();

            $table->index(['tour_id', 'day_id', 'session_token']);
            $table->unique(['tour_id', 'day_id', 'activity_id', 'session_token'], 'aj_tour_activity_selections_tour_day_activity_session_unique');
        });
    }

    public function down(): void
    {
        Schema::connection('wp')->dropIfExists('aj_tour_activity_selections');
    }
};
