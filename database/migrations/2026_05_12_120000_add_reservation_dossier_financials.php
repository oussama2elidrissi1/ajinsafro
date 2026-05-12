<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql');

        if ($schema->hasTable('reservations')) {
            $schema->table('reservations', function (Blueprint $table) use ($schema) {
                if (! $schema->hasColumn('reservations', 'dossier_number')) {
                    $table->string('dossier_number', 40)->nullable()->after('id');
                    $table->index('dossier_number');
                }
                if (! $schema->hasColumn('reservations', 'total_base')) {
                    $table->decimal('total_base', 12, 2)->nullable()->after('base_price');
                }
                if (! $schema->hasColumn('reservations', 'extras_total')) {
                    $table->decimal('extras_total', 12, 2)->nullable()->after('room_supplement_total');
                }
                if (! $schema->hasColumn('reservations', 'total_amount')) {
                    $table->decimal('total_amount', 12, 2)->nullable()->after('extras_total');
                }
                if (! $schema->hasColumn('reservations', 'remaining_amount')) {
                    $table->decimal('remaining_amount', 12, 2)->nullable()->after('paid_amount');
                }
                if (! $schema->hasColumn('reservations', 'payment_status')) {
                    $table->string('payment_status', 32)->nullable()->after('remaining_amount');
                    $table->index('payment_status');
                }
                if (! $schema->hasColumn('reservations', 'dossier_status')) {
                    $table->string('dossier_status', 32)->nullable()->after('payment_status');
                    $table->index('dossier_status');
                }
                if (! $schema->hasColumn('reservations', 'confirmed_at')) {
                    $table->timestamp('confirmed_at')->nullable()->after('dossier_status');
                }
                if (! $schema->hasColumn('reservations', 'cancelled_at')) {
                    $table->timestamp('cancelled_at')->nullable()->after('confirmed_at');
                }
                if (! $schema->hasColumn('reservations', 'assigned_to')) {
                    $table->unsignedBigInteger('assigned_to')->nullable()->after('agent_id');
                    $table->index('assigned_to');
                }
            });
        }

        if ($schema->hasTable('reservation_extras')) {
            $schema->table('reservation_extras', function (Blueprint $table) use ($schema) {
                if (! $schema->hasColumn('reservation_extras', 'voyage_extra_id')) {
                    $table->unsignedBigInteger('voyage_extra_id')->nullable()->after('reservation_id');
                    $table->index('voyage_extra_id');
                }
                if (! $schema->hasColumn('reservation_extras', 'description')) {
                    $table->text('description')->nullable()->after('name');
                }
                if (! $schema->hasColumn('reservation_extras', 'unit_price')) {
                    $table->decimal('unit_price', 12, 2)->nullable()->after('price');
                }
                if (! $schema->hasColumn('reservation_extras', 'quantity')) {
                    $table->unsignedInteger('quantity')->default(1)->after('unit_price');
                }
                if (! $schema->hasColumn('reservation_extras', 'total_price')) {
                    $table->decimal('total_price', 12, 2)->nullable()->after('quantity');
                }
                if (! $schema->hasColumn('reservation_extras', 'application_scope')) {
                    $table->string('application_scope', 32)->nullable()->after('total_price');
                }
                if (! $schema->hasColumn('reservation_extras', 'traveler_keys')) {
                    $table->json('traveler_keys')->nullable()->after('application_scope');
                }
            });
        }

        if (! $schema->hasTable('reservation_payments')) {
            $schema->create('reservation_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('reservation_id');
                $table->date('payment_date');
                $table->string('payment_method', 50);
                $table->decimal('amount', 12, 2);
                $table->string('reference', 120)->nullable();
                $table->string('proof_file', 255)->nullable();
                $table->string('receipt_pdf_path', 255)->nullable();
                $table->text('note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('reservation_id')->references('id')->on('reservations')->onDelete('cascade');
                $table->index(['reservation_id', 'payment_date']);
            });
        }

        if (! $schema->hasTable('reservation_documents')) {
            $schema->create('reservation_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('reservation_id');
                $table->string('type', 50);
                $table->string('title', 190);
                $table->string('file_path', 255);
                $table->string('mime_type', 120)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('reservation_id')->references('id')->on('reservations')->onDelete('cascade');
                $table->index(['reservation_id', 'type']);
            });
        }

        if (! $schema->hasTable('reservation_histories')) {
            $schema->create('reservation_histories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('reservation_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action', 80);
                $table->longText('old_value')->nullable();
                $table->longText('new_value')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();

                $table->foreign('reservation_id')->references('id')->on('reservations')->onDelete('cascade');
                $table->index(['reservation_id', 'action']);
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql');

        $schema->dropIfExists('reservation_histories');
        $schema->dropIfExists('reservation_documents');
        $schema->dropIfExists('reservation_payments');

        if ($schema->hasTable('reservation_extras')) {
            $schema->table('reservation_extras', function (Blueprint $table) use ($schema) {
                $drops = [];
                foreach (['voyage_extra_id', 'description', 'unit_price', 'quantity', 'total_price', 'application_scope', 'traveler_keys'] as $column) {
                    if ($schema->hasColumn('reservation_extras', $column)) {
                        $drops[] = $column;
                    }
                }
                if ($drops !== []) {
                    $table->dropColumn($drops);
                }
            });
        }

        if ($schema->hasTable('reservations')) {
            $schema->table('reservations', function (Blueprint $table) use ($schema) {
                $drops = [];
                foreach (['dossier_number', 'total_base', 'extras_total', 'total_amount', 'remaining_amount', 'payment_status', 'dossier_status', 'confirmed_at', 'cancelled_at', 'assigned_to'] as $column) {
                    if ($schema->hasColumn('reservations', $column)) {
                        $drops[] = $column;
                    }
                }
                if ($drops !== []) {
                    $table->dropColumn($drops);
                }
            });
        }
    }
};
