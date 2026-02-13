<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'wp';

    /**
     * Add day_number and is_optional to aj_tour_hotels and aj_tour_transfers
     * for multi-day circuits (multiple cities/countries).
     */
    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('aj_tour_hotels')) {
            if (!$schema->hasColumn('aj_tour_hotels', 'day_number')) {
                $schema->table('aj_tour_hotels', function (Blueprint $table) {
                    $table->unsignedSmallInteger('day_number')->default(1)->after('tour_id');
                });
            }
            if (!$schema->hasColumn('aj_tour_hotels', 'is_optional')) {
                $schema->table('aj_tour_hotels', function (Blueprint $table) {
                    $table->unsignedTinyInteger('is_optional')->default(0)->after('sort_order');
                });
            }
        }

        if ($schema->hasTable('aj_tour_transfers')) {
            if (!$schema->hasColumn('aj_tour_transfers', 'day_number')) {
                $schema->table('aj_tour_transfers', function (Blueprint $table) {
                    $table->unsignedSmallInteger('day_number')->default(1)->after('direction');
                });
            }
            if (!$schema->hasColumn('aj_tour_transfers', 'is_optional')) {
                $schema->table('aj_tour_transfers', function (Blueprint $table) {
                    $table->unsignedTinyInteger('is_optional')->default(0)->after('sort_order');
                });
            }
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('aj_tour_hotels')) {
            if ($schema->hasColumn('aj_tour_hotels', 'day_number')) {
                $schema->table('aj_tour_hotels', fn (Blueprint $t) => $t->dropColumn('day_number'));
            }
            if ($schema->hasColumn('aj_tour_hotels', 'is_optional')) {
                $schema->table('aj_tour_hotels', fn (Blueprint $t) => $t->dropColumn('is_optional'));
            }
        }

        if ($schema->hasTable('aj_tour_transfers')) {
            if ($schema->hasColumn('aj_tour_transfers', 'day_number')) {
                $schema->table('aj_tour_transfers', fn (Blueprint $t) => $t->dropColumn('day_number'));
            }
            if ($schema->hasColumn('aj_tour_transfers', 'is_optional')) {
                $schema->table('aj_tour_transfers', fn (Blueprint $t) => $t->dropColumn('is_optional'));
            }
        }
    }
};
