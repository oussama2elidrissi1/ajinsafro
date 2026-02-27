<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'aj_activities';

    public function up(): void
    {
        $schema = Schema::connection('wp');

        if (! $schema->hasTable(self::TABLE)) {
            return;
        }

        if (! $schema->hasColumn(self::TABLE, 'is_active')) {
            $schema->table(self::TABLE, function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('location_text');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('wp');

        if (! $schema->hasTable(self::TABLE)) {
            return;
        }

        if ($schema->hasColumn(self::TABLE, 'is_active')) {
            $schema->table(self::TABLE, function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
