<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agent_commission_logs')) {
            return;
        }

        Schema::create('agent_commission_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_entry_id')->constrained('agent_commission_entries')->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30)->nullable();
            $table->decimal('old_amount', 12, 2)->nullable();
            $table->decimal('new_amount', 12, 2)->nullable();
            $table->string('action', 50);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['commission_entry_id', 'created_at'], 'agent_commission_logs_entry_created_index');
            $table->index(['agent_id', 'action'], 'agent_commission_logs_agent_action_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_commission_logs');
    }
};
