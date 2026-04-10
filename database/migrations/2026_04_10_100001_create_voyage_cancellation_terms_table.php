<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voyage_cancellation_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voyage_id')->constrained('voyages')->cascadeOnDelete();
            $table->unsignedSmallInteger('days_before_departure')->default(0);
            $table->decimal('refund_percent', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('note', 500)->nullable();
            $table->timestamps();
            $table->index(['voyage_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voyage_cancellation_terms');
    }
};
