<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Transfert Aller (arrival = Jour 1) / Transfert Retour (departure = Dernier jour).
     * Run on WP connection. arrival = aéroport → hôtel, departure = hôtel → aéroport.
     */
    public function up(): void
    {
        $connection = 'wp';
        $schema = Schema::connection($connection);

        if ($schema->hasTable('aj_tour_transfers')) {
            return;
        }

        $schema->create('aj_tour_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tour_id')->comment('wp_posts.ID');
            $table->enum('direction', ['arrival', 'departure'])->comment('arrival=Jour 1, departure=dernier jour');
            $table->string('from_label')->nullable();
            $table->string('to_label')->nullable();
            $table->time('pickup_time')->nullable();
            $table->time('dropoff_time')->nullable();
            $table->string('vehicle_type', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['tour_id', 'direction']);
            $table->index('tour_id');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('wp');
        if ($schema->hasTable('aj_tour_transfers')) {
            $schema->dropIfExists('aj_tour_transfers');
        }
    }
};
