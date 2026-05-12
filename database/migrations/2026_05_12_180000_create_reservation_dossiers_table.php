<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql');

        if (! $schema->hasTable('reservation_dossiers')) {
            $schema->create('reservation_dossiers', function (Blueprint $table) {
                $table->id();
                $table->string('dossier_number', 40)->nullable()->unique();
                $table->unsignedBigInteger('client_id')->nullable();
                $table->unsignedBigInteger('main_reservation_id')->nullable();
                $table->decimal('total_base', 12, 2)->default(0);
                $table->decimal('room_supplement_total', 12, 2)->default(0);
                $table->decimal('extras_total', 12, 2)->default(0);
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->decimal('remaining_amount', 12, 2)->default(0);
                $table->string('payment_status', 32)->default('non_paid');
                $table->string('dossier_status', 32)->default('pending');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('assigned_to')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['client_id', 'dossier_status']);
                $table->index(['assigned_to', 'payment_status']);
            });
        }

        if ($schema->hasTable('reservations')) {
            $schema->table('reservations', function (Blueprint $table) use ($schema) {
                if (! $schema->hasColumn('reservations', 'reservation_dossier_id')) {
                    $table->unsignedBigInteger('reservation_dossier_id')->nullable()->after('id');
                    $table->index('reservation_dossier_id');
                }
            });
        }

        if ($schema->hasTable('reservation_payments')) {
            $schema->table('reservation_payments', function (Blueprint $table) use ($schema) {
                if (! $schema->hasColumn('reservation_payments', 'reservation_dossier_id')) {
                    $table->unsignedBigInteger('reservation_dossier_id')->nullable()->after('reservation_id');
                    $table->index('reservation_dossier_id');
                }
            });
        }

        if ($schema->hasTable('reservation_documents')) {
            $schema->table('reservation_documents', function (Blueprint $table) use ($schema) {
                if (! $schema->hasColumn('reservation_documents', 'reservation_dossier_id')) {
                    $table->unsignedBigInteger('reservation_dossier_id')->nullable()->after('reservation_id');
                    $table->index('reservation_dossier_id');
                }
            });
        }

        if ($schema->hasTable('reservation_histories')) {
            $schema->table('reservation_histories', function (Blueprint $table) use ($schema) {
                if (! $schema->hasColumn('reservation_histories', 'reservation_dossier_id')) {
                    $table->unsignedBigInteger('reservation_dossier_id')->nullable()->after('reservation_id');
                    $table->index('reservation_dossier_id');
                }
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql');

        foreach (['reservation_histories', 'reservation_documents', 'reservation_payments', 'reservations'] as $tableName) {
            if (! $schema->hasTable($tableName)) {
                continue;
            }

            $schema->table($tableName, function (Blueprint $table) use ($schema, $tableName) {
                if ($schema->hasColumn($tableName, 'reservation_dossier_id')) {
                    $table->dropColumn('reservation_dossier_id');
                }
            });
        }

        $schema->dropIfExists('reservation_dossiers');
    }
};
