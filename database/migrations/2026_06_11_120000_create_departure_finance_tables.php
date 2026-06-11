<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charge_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('departure_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departure_id')->constrained('departures')->cascadeOnDelete();
            $table->foreignId('voyage_id')->nullable()->constrained('voyages')->nullOnDelete();
            $table->foreignId('charge_type_id')->nullable()->constrained('charge_types')->nullOnDelete();
            $table->string('title', 190);
            $table->text('description')->nullable();
            $table->string('supplier_name', 190)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 8)->default('MAD');
            $table->enum('payment_method', ['espece', 'cheque', 'ordre_virement', 'carte', 'en_ligne', 'autre'])->default('autre');
            $table->enum('payment_status', ['non_paye', 'partiel', 'paye'])->default('non_paye');
            $table->date('paid_at')->nullable();
            $table->string('attachment', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['departure_id', 'payment_method']);
            $table->index(['voyage_id', 'charge_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departure_charges');
        Schema::dropIfExists('charge_types');
    }
};
