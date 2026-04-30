<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_deal_participants', function (Blueprint $table) {
            if (! Schema::hasColumn('group_deal_participants', 'group_deal_id')) {
                $table->foreignId('group_deal_id')->nullable()->after('id')->constrained('group_deals')->nullOnDelete();
            }

            if (! Schema::hasColumn('group_deal_participants', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('client_id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('group_deal_participants', 'full_name')) {
                $table->string('full_name')->nullable()->after('reservation_id');
            }

            if (! Schema::hasColumn('group_deal_participants', 'phone')) {
                $table->string('phone')->nullable()->after('full_name');
            }

            if (! Schema::hasColumn('group_deal_participants', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('group_deal_participants', 'participants_count')) {
                $table->unsignedInteger('participants_count')->default(1)->after('email');
            }

            if (! Schema::hasColumn('group_deal_participants', 'selected_price')) {
                $table->decimal('selected_price', 12, 2)->nullable()->after('participants_count');
            }

            if (! Schema::hasColumn('group_deal_participants', 'payment_status')) {
                $table->string('payment_status', 32)->default('pending')->after('selected_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('group_deal_participants', function (Blueprint $table) {
            if (Schema::hasColumn('group_deal_participants', 'group_deal_id')) {
                $table->dropConstrainedForeignId('group_deal_id');
            }

            if (Schema::hasColumn('group_deal_participants', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }

            $columns = ['full_name', 'phone', 'email', 'participants_count', 'selected_price', 'payment_status'];
            $drops = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn('group_deal_participants', $column)));

            if ($drops !== []) {
                $table->dropColumn($drops);
            }
        });
    }
};
