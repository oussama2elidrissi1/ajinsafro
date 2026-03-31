<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('wp');

        if (! $schema->hasTable('aj_activities')) {
            return;
        }

        $schema->table('aj_activities', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('aj_activities', 'activity_type')) {
                $table->string('activity_type', 120)->nullable()->after('title');
            }
            if (! $schema->hasColumn('aj_activities', 'region_name')) {
                $table->string('region_name', 255)->nullable()->after('location_text');
            }
            if (! $schema->hasColumn('aj_activities', 'adult_price')) {
                $table->decimal('adult_price', 10, 2)->nullable()->after('base_price');
            }
            if (! $schema->hasColumn('aj_activities', 'child_price')) {
                $table->decimal('child_price', 10, 2)->nullable()->after('adult_price');
            }
            if (! $schema->hasColumn('aj_activities', 'min_age')) {
                $table->unsignedInteger('min_age')->nullable()->after('child_price');
            }
            if (! $schema->hasColumn('aj_activities', 'max_age')) {
                $table->unsignedInteger('max_age')->nullable()->after('min_age');
            }
            if (! $schema->hasColumn('aj_activities', 'gallery_image_ids')) {
                $table->longText('gallery_image_ids')->nullable()->after('image_id');
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('wp');

        if (! $schema->hasTable('aj_activities')) {
            return;
        }

        $schema->table('aj_activities', function (Blueprint $table) use ($schema) {
            foreach ([
                'gallery_image_ids',
                'max_age',
                'min_age',
                'child_price',
                'adult_price',
                'region_name',
                'activity_type',
            ] as $column) {
                if ($schema->hasColumn('aj_activities', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
