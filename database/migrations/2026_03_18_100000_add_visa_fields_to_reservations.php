<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (!Schema::hasColumn('reservations', 'visa_ok')) {
                $table->boolean('visa_ok')->default(true)->after('notes')
                    ->comment('Visa OK (true) = pas d\'assistance; false = afficher assistant visa');
            }
            if (!Schema::hasColumn('reservations', 'visa_notes')) {
                $table->text('visa_notes')->nullable()->after('visa_ok');
            }
            if (!Schema::hasColumn('reservations', 'visa_status')) {
                $table->string('visa_status', 30)->nullable()->after('visa_notes')
                    ->comment('not_required,pending,approved,rejected');
            }
            if (!Schema::hasColumn('reservations', 'visa_document_path')) {
                $table->string('visa_document_path', 255)->nullable()->after('visa_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['visa_ok', 'visa_notes', 'visa_status', 'visa_document_path']);
        });
    }
};
