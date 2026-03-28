<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('address');
            }
            if (! Schema::hasColumn('users', 'job_title')) {
                $table->string('job_title', 100)->nullable()->after('branch_id');
            }
            if (! Schema::hasColumn('users', 'user_type')) {
                $table->string('user_type', 50)->nullable()->after('job_title'); // agent, chef_commercial, comptable, admin_siege, super_admin
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'branch_id')) {
                $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['branch_id', 'job_title', 'user_type']);
        });
    }
};
