<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_deal_pricing_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voyage_id')->constrained('voyages')->cascadeOnDelete();
            $table->unsignedSmallInteger('min_participants');
            $table->decimal('price_per_person', 10, 2);
            $table->string('label', 100)->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['voyage_id', 'min_participants']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_deal_pricing_tiers');
    }
};
