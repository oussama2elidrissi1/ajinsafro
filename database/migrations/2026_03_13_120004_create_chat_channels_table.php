<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_channels', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30); // direct, branch, global, reservation
            $table->string('name', 190)->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('reservation_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('branch_id');
            $table->index('reservation_id');
        });

        Schema::table('chat_channels', function (Blueprint $table) {
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('reservation_id')->references('id')->on('reservations')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('chat_channels', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['reservation_id']);
            $table->dropForeign(['created_by']);
        });
        Schema::dropIfExists('chat_channels');
    }
};
