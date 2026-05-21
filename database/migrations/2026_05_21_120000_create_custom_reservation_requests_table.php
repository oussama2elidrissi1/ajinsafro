<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_reservation_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 32)->unique();
            $table->string('status', 32)->default('new')->index();
            $table->string('priority', 32)->nullable()->index();
            $table->string('source', 32)->nullable()->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('client_type', 32)->default('particular');
            $table->string('client_name');
            $table->string('client_gender', 8)->nullable();
            $table->string('client_phone', 50);
            $table->string('client_whatsapp', 50)->nullable();
            $table->boolean('whatsapp_same_as_phone')->default(true);
            $table->string('client_email')->nullable();
            $table->json('preferred_channels')->nullable();
            $table->unsignedInteger('adults')->default(1);
            $table->json('children')->nullable();
            $table->json('infants')->nullable();
            $table->text('passengers_note')->nullable();
            $table->string('destination_text')->nullable()->index();
            $table->string('departure_city_text')->nullable();
            $table->date('departure_date')->nullable()->index();
            $table->date('return_date')->nullable();
            $table->boolean('flexible_dates')->default(false);
            $table->decimal('budget_min', 12, 2)->nullable();
            $table->decimal('budget_max', 12, 2)->nullable();
            $table->string('currency', 8)->default('MAD');
            $table->json('services')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('client_notes')->nullable();
            $table->text('admin_response')->nullable();
            $table->decimal('quoted_amount', 12, 2)->nullable();
            $table->foreignId('converted_reservation_id')->nullable()->constrained('reservations')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['created_by', 'assigned_to']);
            $table->index(['created_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_reservation_requests');
    }
};
