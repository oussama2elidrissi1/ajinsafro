<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            if (!Schema::hasColumn('partners', 'partner_type')) {
                $table->string('partner_type', 50)->nullable()->after('pays')
                    ->comment('agence, commercial_independent, point_vente, apporteur_affaires, agence_etranger');
            }
            if (!Schema::hasColumn('partners', 'rib_iban')) {
                $table->string('rib_iban', 100)->nullable()->after('document_path');
            }
            if (!Schema::hasColumn('partners', 'rib_bic')) {
                $table->string('rib_bic', 20)->nullable()->after('rib_iban');
            }
            if (!Schema::hasColumn('partners', 'payment_mode')) {
                $table->string('payment_mode', 50)->nullable()->after('rib_bic')->comment('virement, cheque, etc.');
            }
            if (!Schema::hasColumn('partners', 'contract_path')) {
                $table->string('contract_path', 500)->nullable()->after('payment_mode');
            }
        });

        if (!Schema::hasTable('partner_voyage_access')) {
            Schema::create('partner_voyage_access', function (Blueprint $table) {
                $table->id();
                $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
                $table->unsignedBigInteger('voyage_id');
                $table->timestamps();
                $table->unique(['partner_id', 'voyage_id']);
                $table->foreign('voyage_id')->references('id')->on('voyages')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('partner_commission_rules')) {
            Schema::create('partner_commission_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('partner_id')->nullable()->constrained('partners')->cascadeOnDelete();
                $table->unsignedBigInteger('voyage_id')->nullable()->comment('null = tous les voyages');
                $table->string('type', 20)->default('percent')->comment('percent, fixed');
                $table->decimal('value', 10, 2)->comment('pourcent ou montant fixe');
                $table->integer('min_volume')->nullable()->comment('nombre de ventes pour appliquer (optionnel)');
                $table->date('valid_from')->nullable();
                $table->date('valid_until')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['partner_id', 'voyage_id']);
            });
        }

        $commissionsCreated = false;
        if (!Schema::hasTable('partner_commissions')) {
            Schema::create('partner_commissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
                $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
                $table->unsignedBigInteger('rule_id')->nullable();
                $table->decimal('reservation_total', 12, 2)->default(0);
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('status', 30)->default('calculated')->comment('calculated, pending, validated, paid, cancelled');
                $table->timestamp('validated_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->unsignedBigInteger('payout_id')->nullable();
                $table->timestamps();
                $table->unique('reservation_id');
                $table->index(['partner_id', 'status']);
            });
            $commissionsCreated = true;
        }

        if (!Schema::hasTable('partner_payouts')) {
            Schema::create('partner_payouts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('status', 30)->default('pending')->comment('pending, paid, cancelled');
                $table->timestamp('paid_at')->nullable();
                $table->string('reference', 100)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['partner_id', 'status']);
            });
        }

        if ($commissionsCreated) {
            Schema::table('partner_commissions', function (Blueprint $table) {
                $table->foreign('payout_id')->references('id')->on('partner_payouts')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('partner_documents')) {
            Schema::create('partner_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('partner_id')->nullable()->constrained('partners')->cascadeOnDelete()->comment('null = document global');
                $table->string('type', 50)->comment('contract, commission_grid, conditions, marketing');
                $table->string('name', 255)->nullable();
                $table->string('file_path', 500);
                $table->timestamps();
                $table->index(['partner_id', 'type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_documents');
        if (Schema::hasTable('partner_commissions')) {
            Schema::table('partner_commissions', fn (Blueprint $t) => $t->dropForeign(['payout_id']));
        }
        Schema::dropIfExists('partner_payouts');
        Schema::dropIfExists('partner_commissions');
        Schema::dropIfExists('partner_commission_rules');
        Schema::dropIfExists('partner_voyage_access');

        Schema::table('partners', function (Blueprint $table) {
            $cols = ['partner_type', 'rib_iban', 'rib_bic', 'payment_mode', 'contract_path'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('partners', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
