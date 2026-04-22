<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Align WP front reservations with admin/portal ownership filters.
        // Use sales_manager_id as the operational/audit actor when present.
        DB::table('reservations')
            ->where('catalog_source_code', 'wp_front_v1')
            ->where(function ($q) {
                $q->whereNull('created_by_user_id')
                    ->orWhereNull('created_by')
                    ->orWhereNull('agent_id');
            })
            ->update([
                'created_by_user_id' => DB::raw('COALESCE(created_by_user_id, sales_manager_id)'),
                'created_by' => DB::raw('COALESCE(created_by, sales_manager_id)'),
                'agent_id' => DB::raw('COALESCE(agent_id, sales_manager_id)'),
                'updated_at' => DB::raw('updated_at'),
            ]);
    }

    public function down(): void
    {
        // No-op
    }
};

