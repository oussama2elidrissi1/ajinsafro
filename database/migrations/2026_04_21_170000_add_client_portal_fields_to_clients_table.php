<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('assigned_to');
                $table->index('user_id');
            }
            if (! Schema::hasColumn('clients', 'portal_username')) {
                $table->string('portal_username', 80)->nullable()->after('user_id');
                $table->index('portal_username');
            }
            if (! Schema::hasColumn('clients', 'portal_temp_password')) {
                // Stored temporarily (admin-only) so the customer can receive it after booking.
                $table->string('portal_temp_password', 255)->nullable()->after('portal_username');
            }
            if (! Schema::hasColumn('clients', 'portal_temp_password_created_at')) {
                $table->timestamp('portal_temp_password_created_at')->nullable()->after('portal_temp_password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'portal_temp_password_created_at')) {
                $table->dropColumn('portal_temp_password_created_at');
            }
            if (Schema::hasColumn('clients', 'portal_temp_password')) {
                $table->dropColumn('portal_temp_password');
            }
            if (Schema::hasColumn('clients', 'portal_username')) {
                $table->dropColumn('portal_username');
            }
            if (Schema::hasColumn('clients', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });
    }
};

