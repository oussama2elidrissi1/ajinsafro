<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_request_quotes', function (Blueprint $table) {
            if (! Schema::hasColumn('custom_request_quotes', 'price_pdf_path')) {
                $table->string('price_pdf_path')->nullable()->after('pdf_path');
            }

            if (! Schema::hasColumn('custom_request_quotes', 'price_sent_at')) {
                $table->dateTime('price_sent_at')->nullable()->after('sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('custom_request_quotes', function (Blueprint $table) {
            foreach (['price_pdf_path', 'price_sent_at'] as $column) {
                if (Schema::hasColumn('custom_request_quotes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
