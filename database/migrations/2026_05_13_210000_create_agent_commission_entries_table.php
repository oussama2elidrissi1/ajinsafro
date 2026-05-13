<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agent_commission_entries')) {
            return;
        }

        Schema::create('agent_commission_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->foreignId('voyage_id')->nullable()->constrained('voyages')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->unsignedBigInteger('travel_date_id')->nullable();
            $table->string('client_name', 190)->nullable();
            $table->decimal('reservation_total', 12, 2)->default(0);
            $table->decimal('commission_base_amount', 12, 2)->default(0);
            $table->decimal('commission_adult', 12, 2)->default(0);
            $table->decimal('commission_child', 12, 2)->default(0);
            $table->decimal('commission_baby', 12, 2)->default(0);
            $table->decimal('commission_total', 12, 2)->default(0);
            $table->string('reservation_status', 30)->nullable();
            $table->string('payment_status', 30)->nullable();
            $table->string('commission_status', 30)->default('estimated');
            $table->string('source', 50)->default('reservation_created');
            $table->timestamp('calculated_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('payable_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['reservation_id', 'agent_id'], 'agent_commission_entries_reservation_agent_unique');
            $table->index(['agent_id', 'commission_status'], 'agent_commission_entries_agent_status_index');
            $table->index(['branch_id', 'commission_status'], 'agent_commission_entries_branch_status_index');
            $table->index(['voyage_id', 'travel_date_id'], 'agent_commission_entries_voyage_date_index');
            $table->index('calculated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_commission_entries');
    }
};
