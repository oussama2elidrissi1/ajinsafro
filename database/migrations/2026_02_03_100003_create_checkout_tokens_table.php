<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 100)->unique();
            $table->uuid('session_id')->index();
            $table->foreignId('voyage_id')->constrained('voyages')->onDelete('cascade');
            $table->string('currency', 10);
            $table->timestamp('price_locked_until')->index();
            $table->timestamp('created_at')->useCurrent();
            
            $table->foreign('session_id')->references('id')->on('package_sessions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_tokens');
    }
};
