<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('voyage_id')->constrained('voyages')->onDelete('cascade');
            $table->unsignedInteger('pax_adults')->default(2);
            $table->unsignedInteger('pax_children')->default(0);
            $table->unsignedInteger('pax_infants')->default(0);
            $table->string('currency', 10)->default('MAD');
            $table->longText('state_json')->nullable()->comment('Removed/added/modified selections');
            $table->longText('price_snapshot_json')->nullable()->comment('Optional price snapshot');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index('voyage_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_sessions');
    }
};
