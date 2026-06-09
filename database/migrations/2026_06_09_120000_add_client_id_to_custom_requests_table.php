<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('custom_requests', 'client_id')) {
                $table->foreignId('client_id')->nullable()->after('assigned_to')->constrained('clients')->nullOnDelete();
                $table->index(['client_id', 'created_by']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('custom_requests', function (Blueprint $table): void {
            if (Schema::hasColumn('custom_requests', 'client_id')) {
                $table->dropConstrainedForeignId('client_id');
            }
        });
    }
};
