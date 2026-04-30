<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_deal_pricing_tiers', function (Blueprint $table) {
            if (! Schema::hasColumn('group_deal_pricing_tiers', 'group_deal_id')) {
                $table->foreignId('group_deal_id')->nullable()->after('id')->constrained('group_deals')->nullOnDelete();
            }

            if (! Schema::hasColumn('group_deal_pricing_tiers', 'max_people')) {
                $table->unsignedInteger('max_people')->nullable()->after('min_participants');
            }
        });

        if (Schema::hasColumn('group_deal_pricing_tiers', 'min_participants')) {
            DB::table('group_deal_pricing_tiers')
                ->whereNull('max_people')
                ->update(['max_people' => DB::raw('min_participants')]);
        }
    }

    public function down(): void
    {
        Schema::table('group_deal_pricing_tiers', function (Blueprint $table) {
            if (Schema::hasColumn('group_deal_pricing_tiers', 'group_deal_id')) {
                $table->dropConstrainedForeignId('group_deal_id');
            }

            if (Schema::hasColumn('group_deal_pricing_tiers', 'max_people')) {
                $table->dropColumn('max_people');
            }
        });
    }
};
