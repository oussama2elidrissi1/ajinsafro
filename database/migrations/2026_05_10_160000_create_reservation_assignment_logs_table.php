<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reservation_assignment_logs')) {
            return;
        }

        Schema::create('reservation_assignment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->foreignId('old_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('new_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('old_agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('new_agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('old_sales_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('new_sales_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_assignment_logs');
    }
};
