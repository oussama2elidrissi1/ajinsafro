<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql');

        if (! $schema->hasTable('reservation_extras')) {
            return;
        }

        $schema->table('reservation_extras', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('reservation_extras', 'source_type')) {
                $table->string('source_type', 64)->nullable()->after('passenger_key');
            }
            if (! $schema->hasColumn('reservation_extras', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            }
            if (! $schema->hasColumn('reservation_extras', 'day_activity_id')) {
                $table->unsignedBigInteger('day_activity_id')->nullable()->after('source_id');
            }
            if (! $schema->hasColumn('reservation_extras', 'choice')) {
                $table->string('choice', 32)->nullable()->after('day_activity_id');
            }
            if (! $schema->hasColumn('reservation_extras', 'price_applied')) {
                $table->decimal('price_applied', 12, 2)->nullable()->after('choice');
            }
            if (! $schema->hasColumn('reservation_extras', 'description')) {
                $table->text('description')->nullable()->after('price_applied');
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql');

        if (! $schema->hasTable('reservation_extras')) {
            return;
        }

        $schema->table('reservation_extras', function (Blueprint $table) use ($schema) {
            foreach (['description', 'price_applied', 'choice', 'day_activity_id', 'source_id', 'source_type'] as $column) {
                if ($schema->hasColumn('reservation_extras', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
