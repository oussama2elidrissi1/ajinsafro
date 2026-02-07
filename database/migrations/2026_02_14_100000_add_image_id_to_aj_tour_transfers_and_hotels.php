<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add image_id (WP attachment ID) to tour transfers and hotels for card images in the plugin.
     */
    public function up(): void
    {
        $schema = Schema::connection('wp');

        if ($schema->hasTable('aj_tour_transfers') && !$this->columnExists('aj_tour_transfers', 'image_id')) {
            $schema->table('aj_tour_transfers', function (Blueprint $table) {
                $table->unsignedBigInteger('image_id')->nullable()->after('notes')->comment('WP attachment ID for card image');
            });
        }

        if ($schema->hasTable('aj_tour_hotels') && !$this->columnExists('aj_tour_hotels', 'image_id')) {
            $schema->table('aj_tour_hotels', function (Blueprint $table) {
                $table->unsignedBigInteger('image_id')->nullable()->after('notes')->comment('WP attachment ID for card image');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('wp');
        if ($schema->hasTable('aj_tour_transfers') && $this->columnExists('aj_tour_transfers', 'image_id')) {
            $schema->table('aj_tour_transfers', fn (Blueprint $table) => $table->dropColumn('image_id'));
        }
        if ($schema->hasTable('aj_tour_hotels') && $this->columnExists('aj_tour_hotels', 'image_id')) {
            $schema->table('aj_tour_hotels', fn (Blueprint $table) => $table->dropColumn('image_id'));
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        return Schema::connection('wp')->hasColumn($table, $column);
    }
};
