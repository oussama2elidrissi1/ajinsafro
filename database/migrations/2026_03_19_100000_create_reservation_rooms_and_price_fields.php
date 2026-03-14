<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql');
        if (!$schema->hasTable('reservation_rooms')) {
            $schema->create('reservation_rooms', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('reservation_id');
                $table->unsignedBigInteger('tour_hotel_id')->comment('aj_tour_hotels.id (connexion wp)');
                $table->unsignedBigInteger('tour_hotel_room_id')->comment('aj_tour_hotel_rooms.id (connexion wp)');
                $table->unsignedInteger('room_count')->default(1);
                $table->decimal('supplement_unit', 12, 2)->default(0)->comment('Supplément unitaire (DH) au moment de la résa');
                $table->decimal('supplement_total', 12, 2)->default(0)->comment('room_count * supplement_unit');
                $table->timestamps();

                $table->foreign('reservation_id')->references('id')->on('reservations')->onDelete('cascade');
                $table->index(['reservation_id', 'tour_hotel_id']);
            });
        }

        if ($schema->hasTable('reservations') && !$schema->hasColumn('reservations', 'base_price')) {
            $schema->table('reservations', function (Blueprint $table) {
                $table->decimal('base_price', 12, 2)->nullable()->after('notes')->comment('Prix de base voyage (DH)');
                $table->decimal('room_supplement_total', 12, 2)->nullable()->after('base_price')->comment('Total suppléments chambres (DH)');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql');
        $schema->dropIfExists('reservation_rooms');
        if ($schema->hasTable('reservations') && $schema->hasColumn('reservations', 'base_price')) {
            $schema->table('reservations', function (Blueprint $table) {
                $table->dropColumn(['base_price', 'room_supplement_total']);
            });
        }
    }
};
