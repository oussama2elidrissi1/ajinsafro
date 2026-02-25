<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_admin');
            }

            if (! Schema::hasColumn('users', 'access_mode')) {
                $table->string('access_mode', 20)->default('role')->after('is_active');
            }

            if (! Schema::hasColumn('users', 'base_role')) {
                $table->string('base_role')->nullable()->after('access_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'base_role')) {
                $table->dropColumn('base_role');
            }

            if (Schema::hasColumn('users', 'access_mode')) {
                $table->dropColumn('access_mode');
            }

            if (Schema::hasColumn('users', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
