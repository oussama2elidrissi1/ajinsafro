<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql');

        if ($schema->hasTable('departure_room_allocations')) {
            return;
        }

        $schema->create('departure_room_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departure_id')->constrained('departures')->cascadeOnDelete();
            $table->unsignedBigInteger('hotel_id')->nullable()->comment('Future-ready binding to hotels.id');
            $table->string('room_type', 100);
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('capacity_per_room')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['departure_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('departure_room_allocations');
    }
};
