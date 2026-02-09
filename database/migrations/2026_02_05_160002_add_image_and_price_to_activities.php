<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'aj_activities';

    /**
     * Run the migrations.
     * Add image_id and base_price columns to activities table.
     * Idempotent: checks if columns exist before adding.
     */
    public function up(): void
    {
        $schema = Schema::connection('wp');

        if (!$schema->hasTable(self::TABLE)) {
            return;
        }

        $schema->table(self::TABLE, function (Blueprint $table) {
            if (!$schema->hasColumn(self::TABLE, 'image_id')) {
                $table->unsignedBigInteger('image_id')->nullable()->after('icon');
            }
            if (!$schema->hasColumn(self::TABLE, 'base_price')) {
                $table->decimal('base_price', 10, 2)->nullable()->after('image_id');
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
            if ($schema->hasColumn(self::TABLE, 'image_id')) {
                $table->dropColumn('image_id');
            }
            if ($schema->hasColumn(self::TABLE, 'base_price')) {
                $table->dropColumn('base_price');
            }
        });
    }
};
