<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agency_employees')) {
            return;
        }

        Schema::table('agency_employees', function (Blueprint $table) {
            if (! Schema::hasColumn('agency_employees', 'department')) {
                $table->string('department', 120)->nullable()->after('position');
            }
            if (! Schema::hasColumn('agency_employees', 'employee_type')) {
                $table->string('employee_type', 50)->nullable()->after('department');
            }
            if (! Schema::hasColumn('agency_employees', 'contract_type')) {
                $table->string('contract_type', 80)->nullable()->after('employee_type');
            }
            if (! Schema::hasColumn('agency_employees', 'hire_date')) {
                $table->date('hire_date')->nullable()->after('contract_type');
            }
            if (! Schema::hasColumn('agency_employees', 'exit_date')) {
                $table->date('exit_date')->nullable()->after('hire_date');
            }
            if (! Schema::hasColumn('agency_employees', 'fixed_salary')) {
                $table->decimal('fixed_salary', 12, 2)->nullable()->after('exit_date');
            }
            if (! Schema::hasColumn('agency_employees', 'salary_currency')) {
                $table->string('salary_currency', 10)->nullable()->after('fixed_salary');
            }
            if (! Schema::hasColumn('agency_employees', 'hr_status')) {
                $table->string('hr_status', 30)->nullable()->after('salary_currency');
            }
            if (! Schema::hasColumn('agency_employees', 'national_id')) {
                $table->string('national_id', 100)->nullable()->after('hr_status');
            }
            if (! Schema::hasColumn('agency_employees', 'address')) {
                $table->text('address')->nullable()->after('national_id');
            }
            if (! Schema::hasColumn('agency_employees', 'emergency_contact')) {
                $table->string('emergency_contact', 190)->nullable()->after('address');
            }
            if (! Schema::hasColumn('agency_employees', 'internal_hr_notes')) {
                $table->text('internal_hr_notes')->nullable()->after('emergency_contact');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('agency_employees')) {
            return;
        }

        Schema::table('agency_employees', function (Blueprint $table) {
            foreach ([
                'internal_hr_notes',
                'emergency_contact',
                'address',
                'national_id',
                'hr_status',
                'salary_currency',
                'fixed_salary',
                'exit_date',
                'hire_date',
                'contract_type',
                'employee_type',
                'department',
            ] as $column) {
                if (Schema::hasColumn('agency_employees', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
