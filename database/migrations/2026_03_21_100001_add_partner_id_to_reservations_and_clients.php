<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reservations') && !Schema::hasColumn('reservations', 'partner_id')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->unsignedBigInteger('partner_id')->nullable()->after('id');
                $table->foreign('partner_id')->references('id')->on('partners')->nullOnDelete();
                $table->index('partner_id');
            });
        }

        if (Schema::hasTable('clients') && !Schema::hasColumn('clients', 'partner_id')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->unsignedBigInteger('partner_id')->nullable()->after('id');
                $table->foreign('partner_id')->references('id')->on('partners')->nullOnDelete();
                $table->index('partner_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('reservations', 'partner_id')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropForeign(['partner_id']);
            });
        }
        if (Schema::hasColumn('clients', 'partner_id')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropForeign(['partner_id']);
            });
        }
    }
};
