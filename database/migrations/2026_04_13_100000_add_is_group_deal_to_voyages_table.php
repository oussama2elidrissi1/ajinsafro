<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            if (! Schema::hasColumn('voyages', 'is_group_deal')) {
                $table->boolean('is_group_deal')->default(false)->after('is_featured');
            }
        });
    }

    public function down(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            if (Schema::hasColumn('voyages', 'is_group_deal')) {
                $table->dropColumn('is_group_deal');
            }
        });
    }
};
