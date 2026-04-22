<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaultBranchId = (int) DB::table('branches')
            ->where(function ($q) {
                $q->whereNull('is_active')->orWhere('is_active', 1);
            })
            ->orderBy('id')
            ->value('id');

        if ($defaultBranchId <= 0) {
            $defaultBranchId = (int) DB::table('branches')->orderBy('id')->value('id');
        }

        if ($defaultBranchId <= 0) {
            return;
        }

        $managerId = DB::table('branches')->where('id', $defaultBranchId)->value('manager_user_id');
        $managerId = $managerId ? (int) $managerId : null;

        DB::table('reservations')
            ->whereNull('branch_id')
            ->where('catalog_source_code', 'wp_front_v1')
            ->update([
                'branch_id' => $defaultBranchId,
                'sales_manager_id' => DB::raw('COALESCE(sales_manager_id, ' . ($managerId ? (int) $managerId : 'NULL') . ')'),
                'updated_at' => DB::raw('updated_at'),
            ]);
    }

    public function down(): void
    {
        // No-op (we don't want to remove ownership once assigned).
    }
};

