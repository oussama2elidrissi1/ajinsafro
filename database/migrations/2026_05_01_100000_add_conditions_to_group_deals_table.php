<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_deals', function (Blueprint $table) {
            $table->text('conditions')->nullable()->after('program');
        });
    }

    public function down(): void
    {
        Schema::table('group_deals', function (Blueprint $table) {
            $table->dropColumn('conditions');
        });
    }
};
