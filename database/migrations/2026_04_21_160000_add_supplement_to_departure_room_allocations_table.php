<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql');

        if (! $schema->hasTable('departure_room_allocations')) {
            return;
        }

        $schema->table('departure_room_allocations', function (Blueprint $table) {
            if ($this->hasColumn($table, 'supplement')) {
                return;
            }

            $table->decimal('supplement', 10, 2)
                ->default(0)
                ->after('capacity_per_room')
                ->comment('Supplement per person (MAD/DH) for this room type');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql');
        if (! $schema->hasTable('departure_room_allocations')) {
            return;
        }

        $schema->table('departure_room_allocations', function (Blueprint $table) {
            if ($this->hasColumn($table, 'supplement')) {
                $table->dropColumn('supplement');
            }
        });
    }

    /**
     * Blueprint doesn't expose schema inspection; use Schema builder.
     */
    private function hasColumn(Blueprint $table, string $column): bool
    {
        try {
            return Schema::connection('mysql')->hasColumn($table->getTable(), $column);
        } catch (\Throwable) {
            return false;
        }
    }
};

