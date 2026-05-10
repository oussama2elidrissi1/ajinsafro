<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (! Schema::hasColumn('branches', 'agency_type')) {
                $table->string('agency_type', 30)->default('internal')->after('type');
            }
            if (! Schema::hasColumn('branches', 'status')) {
                $table->string('status', 30)->default('active')->after('manager_user_id');
            }
            if (! Schema::hasColumn('branches', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('email');
            }
            if (! Schema::hasColumn('branches', 'default_commission_rate')) {
                $table->decimal('default_commission_rate', 8, 2)->nullable()->after('logo_path');
            }
            if (! Schema::hasColumn('branches', 'currency')) {
                $table->string('currency', 10)->default('MAD')->after('default_commission_rate');
            }
            if (! Schema::hasColumn('branches', 'business_hours')) {
                $table->text('business_hours')->nullable()->after('currency');
            }
            if (! Schema::hasColumn('branches', 'internal_notes')) {
                $table->text('internal_notes')->nullable()->after('business_hours');
            }
            if (! Schema::hasColumn('branches', 'documents')) {
                $table->json('documents')->nullable()->after('internal_notes');
            }
            if (! Schema::hasColumn('branches', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('documents');
            }
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $columns = [
                'agency_type',
                'status',
                'logo_path',
                'default_commission_rate',
                'currency',
                'business_hours',
                'internal_notes',
                'documents',
                'archived_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('branches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
