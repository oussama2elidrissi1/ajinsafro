<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('custom_request_quote_days')) {
            Schema::create('custom_request_quote_days', function (Blueprint $table) {
                $table->id();
                $table->foreignId('custom_request_quote_id')->constrained('custom_request_quotes')->cascadeOnDelete();
                $table->unsignedInteger('day_number')->default(1);
                $table->date('date')->nullable();
                $table->string('title')->nullable();
                $table->string('city')->nullable();
                $table->text('client_description')->nullable();
                $table->text('internal_notes')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['custom_request_quote_id', 'sort_order'], 'cr_quote_days_quote_sort_idx');
            });
        }

        Schema::table('custom_request_quotes', function (Blueprint $table) {
            if (! Schema::hasColumn('custom_request_quotes', 'offline_agent_id')) {
                $table->foreignId('offline_agent_id')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('custom_request_quotes', 'response_deadline')) {
                $table->date('response_deadline')->nullable()->after('valid_until');
            }

            if (! Schema::hasColumn('custom_request_quotes', 'validated_at')) {
                $table->dateTime('validated_at')->nullable()->after('sent_at');
            }
        });

        Schema::table('custom_request_quote_items', function (Blueprint $table) {
            if (! Schema::hasColumn('custom_request_quote_items', 'custom_request_quote_day_id')) {
                $table->foreignId('custom_request_quote_day_id')
                    ->nullable()
                    ->after('custom_request_quote_id')
                    ->constrained('custom_request_quote_days')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('custom_request_quote_items', 'title')) {
                $table->string('title')->nullable()->after('service_type');
            }

            if (! Schema::hasColumn('custom_request_quote_items', 'margin_type')) {
                $table->enum('margin_type', ['amount', 'percent'])->default('amount')->after('unit_purchase_price');
            }

            if (! Schema::hasColumn('custom_request_quote_items', 'margin_value')) {
                $table->decimal('margin_value', 12, 2)->default(0)->after('margin_type');
            }

            if (! Schema::hasColumn('custom_request_quote_items', 'is_optional')) {
                $table->boolean('is_optional')->default(false)->after('total_sale');
            }

            if (! Schema::hasColumn('custom_request_quote_items', 'data_json')) {
                $table->json('data_json')->nullable()->after('is_optional');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE custom_request_quote_items MODIFY service_type ENUM('hotel','flight','transfer','visa','insurance','activity','excursion','guide','catering','transport','other') NOT NULL");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('custom_request_quote_items') && DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE custom_request_quote_items MODIFY service_type ENUM('hotel','flight','transfer','visa','insurance','activity','transport','other') NOT NULL");
        }

        Schema::table('custom_request_quote_items', function (Blueprint $table) {
            foreach (['data_json', 'is_optional', 'margin_value', 'margin_type', 'title'] as $column) {
                if (Schema::hasColumn('custom_request_quote_items', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('custom_request_quote_items', 'custom_request_quote_day_id')) {
                $table->dropConstrainedForeignId('custom_request_quote_day_id');
            }
        });

        Schema::table('custom_request_quotes', function (Blueprint $table) {
            if (Schema::hasColumn('custom_request_quotes', 'offline_agent_id')) {
                $table->dropConstrainedForeignId('offline_agent_id');
            }

            foreach (['response_deadline', 'validated_at'] as $column) {
                if (Schema::hasColumn('custom_request_quotes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('custom_request_quote_days');
    }
};
