<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reservations')) {
            return;
        }

        Schema::table('reservations', function (Blueprint $table) {
            if (! Schema::hasColumn('reservations', 'channel')) {
                $table->string('channel', 40)->nullable()->after('wp_tour_post_id')->index();
            }
        });

        $frontSources = ['wp_front_v1', 'front_kiosk'];

        DB::table('reservations')
            ->whereNull('channel')
            ->where(function ($q) use ($frontSources) {
                $q->whereIn('catalog_source_code', $frontSources)
                    ->orWhereExists(function ($exists) {
                        $exists->selectRaw('1')
                            ->from('clients')
                            ->whereColumn('clients.id', 'reservations.client_external_id')
                            ->whereNotNull('clients.user_id');
                    });
            })
            ->update([
                'channel' => 'client',
                'updated_at' => DB::raw('updated_at'),
            ]);

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
            ->where('channel', 'client')
            ->whereNull('branch_id')
            ->update([
                'branch_id' => $defaultBranchId,
                'sales_manager_id' => DB::raw('COALESCE(sales_manager_id, '.($managerId ? (int) $managerId : 'NULL').')'),
                'updated_at' => DB::raw('updated_at'),
            ]);

        DB::table('reservations')
            ->where('channel', 'client')
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
        if (! Schema::hasTable('reservations') || ! Schema::hasColumn('reservations', 'channel')) {
            return;
        }

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('channel');
        });
    }
};
