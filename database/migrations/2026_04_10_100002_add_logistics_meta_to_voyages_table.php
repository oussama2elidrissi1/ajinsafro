<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            if (! Schema::hasColumn('voyages', 'logistics_meta')) {
                $table->json('logistics_meta')->nullable()->after('gallery_wp_ids');
            }
        });
    }

    public function down(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            if (Schema::hasColumn('voyages', 'logistics_meta')) {
                $table->dropColumn('logistics_meta');
            }
        });
    }
};
