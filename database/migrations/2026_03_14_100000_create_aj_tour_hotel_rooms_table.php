<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('wp');
        if ($schema->hasTable('aj_tour_hotel_rooms')) {
            return;
        }
        $schema->create('aj_tour_hotel_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tour_hotel_id')->comment('aj_tour_hotels.id');
            $table->string('room_type', 100)->comment('Single, Double, Twin, Triple, Suite, etc.');
            $table->string('room_label', 255)->nullable();
            $table->string('room_code', 50)->nullable()->comment('Référence interne');
            $table->unsignedInteger('room_count')->default(1);
            $table->unsignedInteger('capacity_adults')->default(0);
            $table->unsignedInteger('capacity_children')->default(0);
            $table->unsignedInteger('capacity_total')->default(1);
            $table->decimal('supplement', 12, 2)->default(0)->comment('Supplément en DH');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_default')->default(false)->comment('Chambre recommandée par défaut');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('tour_hotel_id');
        });
    }

    public function down(): void
    {
        Schema::connection('wp')->dropIfExists('aj_tour_hotel_rooms');
    }
};
