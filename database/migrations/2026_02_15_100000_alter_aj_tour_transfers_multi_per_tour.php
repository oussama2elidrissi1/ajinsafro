<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'wp';

    /**
     * Allow multiple transfers per tour (multiple arrival, multiple departure).
     * Drop unique(tour_id, direction), add sort_order.
     */
    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        if (!$schema->hasTable('aj_tour_transfers')) {
            return;
        }

        try {
            $schema->table('aj_tour_transfers', function (Blueprint $table) {
                $table->dropUnique(['tour_id', 'direction']);
            });
        } catch (\Throwable $e) {
            $prefix = DB::connection($this->connection)->getTablePrefix();
            $fullTable = $prefix . 'aj_tour_transfers';
            foreach (['aj_tour_transfers_tour_id_direction_unique', 'tour_id_direction_unique'] as $name) {
                try {
                    DB::connection($this->connection)->statement("ALTER TABLE `{$fullTable}` DROP INDEX `{$name}`");
                    break;
                } catch (\Throwable $e2) {
                    // continue
                }
            }
        }

        if (!$schema->hasColumn('aj_tour_transfers', 'sort_order')) {
            $schema->table('aj_tour_transfers', function (Blueprint $table) {
                $table->unsignedSmallInteger('sort_order')->default(0)->after('direction');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        if (!$schema->hasTable('aj_tour_transfers')) {
            return;
        }
        if ($schema->hasColumn('aj_tour_transfers', 'sort_order')) {
            $schema->table('aj_tour_transfers', fn (Blueprint $t) => $t->dropColumn('sort_order'));
        }
        try {
            $schema->table('aj_tour_transfers', fn (Blueprint $t) => $t->unique(['tour_id', 'direction']));
        } catch (\Throwable $e) {
            //
        }
    }
};
