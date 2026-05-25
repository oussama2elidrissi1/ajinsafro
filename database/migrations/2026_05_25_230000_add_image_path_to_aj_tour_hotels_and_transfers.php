<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add image_path (Laravel public disk path) to tour transfers and hotels.
     *
     * This enables direct uploads from the Laravel admin without creating WP attachments.
     */
    public function up(): void
    {
        $schema = Schema::connection('wp');

        if ($schema->hasTable('aj_tour_transfers') && !$this->columnExists('aj_tour_transfers', 'image_path')) {
            $schema->table('aj_tour_transfers', function (Blueprint $table) {
                $table->string('image_path', 512)->nullable()->after('image_id')->comment('Laravel public disk path for card image');
            });
        }

        if ($schema->hasTable('aj_tour_hotels') && !$this->columnExists('aj_tour_hotels', 'image_path')) {
            $schema->table('aj_tour_hotels', function (Blueprint $table) {
                $table->string('image_path', 512)->nullable()->after('image_id')->comment('Laravel public disk path for card image');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('wp');
        if ($schema->hasTable('aj_tour_transfers') && $this->columnExists('aj_tour_transfers', 'image_path')) {
            $schema->table('aj_tour_transfers', fn (Blueprint $table) => $table->dropColumn('image_path'));
        }
        if ($schema->hasTable('aj_tour_hotels') && $this->columnExists('aj_tour_hotels', 'image_path')) {
            $schema->table('aj_tour_hotels', fn (Blueprint $table) => $table->dropColumn('image_path'));
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        return Schema::connection('wp')->hasColumn($table, $column);
    }
};

