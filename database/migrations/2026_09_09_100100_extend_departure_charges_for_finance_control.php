<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Etend la table existante `departure_charges` au lieu d'en creer une nouvelle.
 *
 * La colonne historique `amount` conserve exactement son sens (= montant reel de la charge) :
 * aucune page ni service existant n'est impacte. On ajoute autour d'elle le prevu, le paye,
 * le fournisseur, l'agence, l'echeancier et la piste d'audit de validation.
 *
 * `payment_status` (enum non_paye/partiel/paye) est conservee et reste synchronisee avec le
 * nouveau `status` afin que DepartureFinanceService et les vues actuelles continuent de
 * fonctionner a l'identique.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('departure_charges')) {
            return;
        }

        Schema::table('departure_charges', function (Blueprint $table) {
            if (! Schema::hasColumn('departure_charges', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('voyage_id');
            }
            if (! Schema::hasColumn('departure_charges', 'supplier_id')) {
                $table->unsignedBigInteger('supplier_id')->nullable()->after('charge_type_id');
            }
            if (! Schema::hasColumn('departure_charges', 'status')) {
                $table->string('status', 30)->default('engagee')->after('payment_status');
            }
            if (! Schema::hasColumn('departure_charges', 'planned_amount')) {
                $table->decimal('planned_amount', 12, 2)->nullable()->after('amount');
            }
            if (! Schema::hasColumn('departure_charges', 'paid_amount')) {
                $table->decimal('paid_amount', 12, 2)->default(0)->after('planned_amount');
            }
            if (! Schema::hasColumn('departure_charges', 'charge_date')) {
                $table->date('charge_date')->nullable()->after('status');
            }
            if (! Schema::hasColumn('departure_charges', 'due_date')) {
                $table->date('due_date')->nullable()->after('charge_date');
            }
            if (! Schema::hasColumn('departure_charges', 'invoice_reference')) {
                $table->string('invoice_reference', 120)->nullable()->after('paid_at');
            }
            if (! Schema::hasColumn('departure_charges', 'notes')) {
                $table->text('notes')->nullable()->after('attachment');
            }
            if (! Schema::hasColumn('departure_charges', 'validated_by')) {
                $table->unsignedBigInteger('validated_by')->nullable()->after('updated_by');
            }
            if (! Schema::hasColumn('departure_charges', 'validated_at')) {
                $table->timestamp('validated_at')->nullable()->after('validated_by');
            }
        });

        Schema::table('departure_charges', function (Blueprint $table) {
            if (Schema::hasColumn('departure_charges', 'status')) {
                $table->index(['status', 'charge_date'], 'departure_charges_status_date_index');
            }
            if (Schema::hasColumn('departure_charges', 'supplier_id')) {
                $table->index('supplier_id', 'departure_charges_supplier_index');
            }
            if (Schema::hasColumn('departure_charges', 'branch_id')) {
                $table->index('branch_id', 'departure_charges_branch_index');
            }
            if (Schema::hasColumn('departure_charges', 'due_date')) {
                $table->index('due_date', 'departure_charges_due_date_index');
            }
        });

        $this->addForeign('departure_charges', 'branch_id', 'branches');
        $this->addForeign('departure_charges', 'supplier_id', 'finance_suppliers');
        $this->addForeign('departure_charges', 'validated_by', 'users');

        $this->backfill();
    }

    public function down(): void
    {
        if (! Schema::hasTable('departure_charges')) {
            return;
        }

        foreach ([
            'departure_charges_status_date_index',
            'departure_charges_supplier_index',
            'departure_charges_branch_index',
            'departure_charges_due_date_index',
        ] as $index) {
            try {
                Schema::table('departure_charges', fn (Blueprint $table) => $table->dropIndex($index));
            } catch (\Throwable $e) {
                // Index absent : rien a faire.
            }
        }

        foreach (['branch_id', 'supplier_id', 'validated_by'] as $column) {
            try {
                Schema::table('departure_charges', fn (Blueprint $table) => $table->dropForeign(['departure_charges_'.$column.'_foreign']));
            } catch (\Throwable $e) {
                // Contrainte absente : rien a faire.
            }
        }

        Schema::table('departure_charges', function (Blueprint $table) {
            foreach ([
                'branch_id', 'supplier_id', 'status', 'planned_amount', 'paid_amount',
                'charge_date', 'due_date', 'invoice_reference', 'notes', 'validated_by', 'validated_at',
            ] as $column) {
                if (Schema::hasColumn('departure_charges', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Aligne les charges historiques sur le nouveau modele sans en modifier le montant reel.
     */
    private function backfill(): void
    {
        // Prevu = reel pour les charges deja saisies : elles ont ete enregistrees a posteriori.
        DB::table('departure_charges')->whereNull('planned_amount')->update([
            'planned_amount' => DB::raw('amount'),
        ]);

        // Date de charge = date de paiement connue, sinon date de creation.
        DB::table('departure_charges')
            ->whereNull('charge_date')
            ->update(['charge_date' => DB::raw('COALESCE(paid_at, DATE(created_at))')]);

        // Statut derive de payment_status, seule information de paiement disponible avant ce module.
        DB::table('departure_charges')->where('payment_status', 'paye')->update([
            'status' => 'payee',
            'paid_amount' => DB::raw('amount'),
        ]);
        DB::table('departure_charges')->where('payment_status', 'partiel')->update([
            'status' => 'partiellement_payee',
        ]);
        DB::table('departure_charges')->where('payment_status', 'non_paye')->update([
            'status' => 'engagee',
        ]);

        // L'agence de rattachement est celle du depart, deduite des reservations qui le portent.
        if (Schema::hasTable('reservations') && Schema::hasColumn('reservations', 'branch_id')) {
            $rows = DB::table('reservations')
                ->selectRaw('departure_id, MIN(branch_id) as branch_id')
                ->whereNotNull('departure_id')
                ->whereNotNull('branch_id')
                ->groupBy('departure_id')
                ->get();

            foreach ($rows as $row) {
                DB::table('departure_charges')
                    ->where('departure_id', $row->departure_id)
                    ->whereNull('branch_id')
                    ->update(['branch_id' => $row->branch_id]);
            }
        }
    }

    private function addForeign(string $table, string $column, string $references): void
    {
        if (! Schema::hasTable($references) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($column, $references) {
                $blueprint->foreign($column)->references('id')->on($references)->nullOnDelete();
            });
        } catch (\Throwable $e) {
            // Contrainte deja presente ou schema legacy : integrite geree applicativement.
        }
    }
};
