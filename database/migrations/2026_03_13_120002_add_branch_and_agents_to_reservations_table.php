<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (! Schema::hasColumn('reservations', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('id');
            }
            if (! Schema::hasColumn('reservations', 'sales_manager_id')) {
                $table->unsignedBigInteger('sales_manager_id')->nullable()->after('branch_id');
            }
            if (! Schema::hasColumn('reservations', 'agent_id')) {
                $table->unsignedBigInteger('agent_id')->nullable()->after('sales_manager_id');
            }
            if (! Schema::hasColumn('reservations', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('reservations', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
        });

        Schema::table('reservations', function (Blueprint $table) {
            if (Schema::hasColumn('reservations', 'branch_id')) {
                $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            }
            if (Schema::hasColumn('reservations', 'sales_manager_id')) {
                $table->foreign('sales_manager_id')->references('id')->on('users')->nullOnDelete();
            }
            if (Schema::hasColumn('reservations', 'agent_id')) {
                $table->foreign('agent_id')->references('id')->on('users')->nullOnDelete();
            }
            if (Schema::hasColumn('reservations', 'created_by')) {
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            }
            if (Schema::hasColumn('reservations', 'updated_by')) {
                $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['sales_manager_id']);
            $table->dropForeign(['agent_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['branch_id', 'sales_manager_id', 'agent_id', 'created_by', 'updated_by']);
        });
    }
};
