<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            if (! Schema::hasColumn('reservations', 'assignment_priority')) {
                $table->string('assignment_priority', 30)->nullable()->after('agent_id');
            }

            if (! Schema::hasColumn('reservations', 'assignment_note')) {
                $table->text('assignment_note')->nullable()->after('assignment_priority');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            if (Schema::hasColumn('reservations', 'assignment_note')) {
                $table->dropColumn('assignment_note');
            }

            if (Schema::hasColumn('reservations', 'assignment_priority')) {
                $table->dropColumn('assignment_priority');
            }
        });
    }
};