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
                $table->string('source_type', 32)->nullable()->after('voyage_extra_id');
                $table->index('source_type');
            }

            if (! $schema->hasColumn('reservation_extras', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
                $table->index('source_id');
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
            if ($schema->hasColumn('reservation_extras', 'source_id')) {
                $table->dropColumn('source_id');
            }
            if ($schema->hasColumn('reservation_extras', 'source_type')) {
                $table->dropColumn('source_type');
            }
        });
    }
};

