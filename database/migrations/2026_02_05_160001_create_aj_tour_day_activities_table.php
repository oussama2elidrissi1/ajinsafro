<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'aj_tour_day_activities';

    /**
     * Run the migrations.
     * Pivot: activities per day (tour_id = wp_posts.ID, day_id = aj_tour_days.id).
     * Idempotent: skip create if table already exists (connexion wp applique le préfixe).
     */
    public function up(): void
    {
        if (Schema::connection('wp')->hasTable(self::TABLE)) {
            return;
        }

        Schema::connection('wp')->create(self::TABLE, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tour_id')->comment('wp_posts.ID (st_tours)');
            $table->unsignedBigInteger('day_id')->comment('aj_tour_days.id');
            $table->unsignedBigInteger('activity_id');
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('is_included')->default(1);
            $table->unsignedTinyInteger('is_mandatory')->default(0);
            $table->unsignedTinyInteger('is_editable')->default(1);
            $table->string('custom_title')->nullable();
            $table->longText('custom_description')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();

            $table->index(['tour_id', 'day_id']);
            $table->index('activity_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('wp')->dropIfExists(self::TABLE);
    }
};
