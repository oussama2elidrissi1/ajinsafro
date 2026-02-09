<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'aj_tour_day_activities';

    /**
     * Run the migrations.
     * Add custom_price column to tour_day_activities pivot table.
     * Idempotent: checks if column exists before adding.
     */
    public function up(): void
    {
        $schema = Schema::connection('wp');

        if (!$schema->hasTable(self::TABLE)) {
            return;
        }

        $schema->table(self::TABLE, function (Blueprint $table) {
            if (!$schema->hasColumn(self::TABLE, 'custom_price')) {
                $table->decimal('custom_price', 10, 2)->nullable()->after('end_time');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $schema = Schema::connection('wp');

        if (!$schema->hasTable(self::TABLE)) {
            return;
        }

        $schema->table(self::TABLE, function (Blueprint $table) {
            if ($schema->hasColumn(self::TABLE, 'custom_price')) {
                $table->dropColumn('custom_price');
            }
        });
    }
};
