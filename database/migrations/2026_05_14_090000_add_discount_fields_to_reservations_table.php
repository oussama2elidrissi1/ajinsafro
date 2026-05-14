<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            if (! Schema::hasColumn('reservations', 'unit_price_before_discount')) {
                $table->decimal('unit_price_before_discount', 12, 2)->nullable()->after('base_price');
            }
            if (! Schema::hasColumn('reservations', 'discount_type')) {
                $table->string('discount_type', 20)->nullable()->after('unit_price_before_discount');
            }
            if (! Schema::hasColumn('reservations', 'discount_value')) {
                $table->decimal('discount_value', 12, 2)->nullable()->after('discount_type');
            }
            if (! Schema::hasColumn('reservations', 'unit_price_after_discount')) {
                $table->decimal('unit_price_after_discount', 12, 2)->nullable()->after('discount_value');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            foreach (['unit_price_after_discount', 'discount_value', 'discount_type', 'unit_price_before_discount'] as $column) {
                if (Schema::hasColumn('reservations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
