<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_request_quotes', function (Blueprint $table): void {
            if (! Schema::hasColumn('custom_request_quotes', 'summary_mode')) {
                $table->boolean('summary_mode')->default(false)->after('internal_notes');
                $table->index(['custom_request_id', 'summary_mode']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('custom_request_quotes', function (Blueprint $table): void {
            if (Schema::hasColumn('custom_request_quotes', 'summary_mode')) {
                $table->dropIndex(['custom_request_id', 'summary_mode']);
                $table->dropColumn('summary_mode');
            }
        });
    }
};
