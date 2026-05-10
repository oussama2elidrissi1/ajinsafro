<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('branches')) {
            return;
        }

        Schema::table('branches', function (Blueprint $table) {
            if (! Schema::hasColumn('branches', 'monthly_revenue_target')) {
                $table->decimal('monthly_revenue_target', 12, 2)->nullable()->after('default_commission_rate');
            }
            if (! Schema::hasColumn('branches', 'monthly_reservations_target')) {
                $table->unsignedInteger('monthly_reservations_target')->nullable()->after('monthly_revenue_target');
            }
            if (! Schema::hasColumn('branches', 'default_commission_type')) {
                $table->string('default_commission_type', 20)->nullable()->after('monthly_reservations_target');
            }
            if (! Schema::hasColumn('branches', 'default_commission_value')) {
                $table->decimal('default_commission_value', 12, 2)->nullable()->after('default_commission_type');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('branches')) {
            return;
        }

        Schema::table('branches', function (Blueprint $table) {
            foreach ([
                'default_commission_value',
                'default_commission_type',
                'monthly_reservations_target',
                'monthly_revenue_target',
            ] as $column) {
                if (Schema::hasColumn('branches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
