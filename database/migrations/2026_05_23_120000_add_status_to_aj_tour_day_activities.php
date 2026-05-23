<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'aj_tour_day_activities';

    public function up(): void
    {
        $schema = Schema::connection('wp');

        if (! $schema->hasTable(self::TABLE)) {
            return;
        }

        if (! $schema->hasColumn(self::TABLE, 'status')) {
            $schema->table(self::TABLE, function (Blueprint $table): void {
                $table->string('status', 32)->default('included')->after('is_included');
                $table->index('status');
            });
        }

        DB::connection('wp')->table(self::TABLE)
            ->where(function ($query): void {
                $query->whereNull('status')->orWhere('status', '');
            })
            ->update([
                'status' => DB::raw("CASE WHEN is_included = 1 THEN 'included' ELSE 'optional' END"),
            ]);
    }

    public function down(): void
    {
        $schema = Schema::connection('wp');

        if (! $schema->hasTable(self::TABLE) || ! $schema->hasColumn(self::TABLE, 'status')) {
            return;
        }

        $schema->table(self::TABLE, function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
