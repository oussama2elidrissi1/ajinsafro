<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_branch_id')->nullable()->comment('Agence expéditrice');
            $table->string('subject', 255);
            $table->text('body');
            $table->timestamps();

            $table->foreign('from_branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->index('from_branch_id');
            $table->index('created_at');
        });

        Schema::create('message_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('message_id');
            $table->timestamp('read_at');
            $table->timestamps();

            $table->unique(['user_id', 'message_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('message_id')->references('id')->on('reservation_messages')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reads');
        Schema::dropIfExists('reservation_messages');
    }
};
