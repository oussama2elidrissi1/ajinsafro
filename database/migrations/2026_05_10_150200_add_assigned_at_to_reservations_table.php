<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reservations') || Schema::hasColumn('reservations', 'assigned_at')) {
            return;
        }

        Schema::table('reservations', function (Blueprint $table) {
            $table->timestamp('assigned_at')->nullable()->after('agent_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('reservations') || ! Schema::hasColumn('reservations', 'assigned_at')) {
            return;
        }

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('assigned_at');
        });
    }
};
