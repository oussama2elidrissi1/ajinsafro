<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departures', function (Blueprint $table) {
            if (! Schema::hasColumn('departures', 'group_deal_enabled')) {
                $table->boolean('group_deal_enabled')->default(false)->after('notes');
            }
            if (! Schema::hasColumn('departures', 'min_participants')) {
                $table->unsignedTinyInteger('min_participants')->default(1)->after('group_deal_enabled');
            }
            if (! Schema::hasColumn('departures', 'guaranteed_threshold')) {
                $table->unsignedTinyInteger('guaranteed_threshold')->default(10)->after('min_participants');
            }
            if (! Schema::hasColumn('departures', 'is_guaranteed')) {
                $table->boolean('is_guaranteed')->default(false)->after('guaranteed_threshold');
            }
            if (! Schema::hasColumn('departures', 'guaranteed_at')) {
                $table->timestamp('guaranteed_at')->nullable()->after('is_guaranteed');
            }
            if (! Schema::hasColumn('departures', 'active_tier_price')) {
                $table->decimal('active_tier_price', 10, 2)->nullable()->after('guaranteed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('departures', function (Blueprint $table) {
            $cols = ['group_deal_enabled', 'min_participants', 'guaranteed_threshold', 'is_guaranteed', 'guaranteed_at', 'active_tier_price'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('departures', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
