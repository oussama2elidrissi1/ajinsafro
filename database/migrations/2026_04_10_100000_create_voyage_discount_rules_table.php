<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voyage_discount_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voyage_id')->constrained('voyages')->cascadeOnDelete();
            $table->string('reduction_type', 16)->default('percent');
            $table->string('scope', 24)->default('global');
            $table->string('condition_type', 32)->default('none');
            $table->json('condition_json')->nullable();
            $table->decimal('value', 12, 2)->default(0);
            $table->unsignedSmallInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['voyage_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voyage_discount_rules');
    }
};
