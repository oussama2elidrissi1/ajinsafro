<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module « Finance & Controle » : tables reellement absentes du projet.
 *
 * - finance_suppliers    : aucun referentiel fournisseur n'existait (admin.partners.fournisseurs
 *                          est une page vitrine statique, sans table ni modele).
 * - structural_expenses  : aucune table de charges de structure (departure_charges est
 *                          exclusivement rattachee a un depart).
 * - financial_documents  : aucune table de justificatifs transverses.
 *
 * Les charges de voyage NE SONT PAS recreees : departure_charges est etendue par la
 * migration 2026_09_09_100100.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('finance_suppliers')) {
            Schema::create('finance_suppliers', function (Blueprint $table) {
                $table->id();
                $table->string('name', 190);
                $table->string('type', 40)->default('autre');
                $table->string('contact_name', 190)->nullable();
                $table->string('email', 190)->nullable();
                $table->string('phone', 40)->nullable();
                $table->string('city', 120)->nullable();
                $table->string('country', 120)->nullable();
                $table->string('address', 255)->nullable();
                $table->string('tax_id', 60)->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['is_active', 'name']);
                $table->index('type');
            });

            $this->addForeign('finance_suppliers', 'created_by', 'users');
            $this->addForeign('finance_suppliers', 'updated_by', 'users');
        }

        if (! Schema::hasTable('structural_expenses')) {
            Schema::create('structural_expenses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->unsignedBigInteger('supplier_id')->nullable();
                $table->string('category', 60);
                $table->string('label', 190);
                $table->text('description')->nullable();
                $table->decimal('amount', 12, 2);
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->string('currency', 8)->default('MAD');
                $table->string('status', 30)->default('prevue');
                $table->date('expense_date');
                $table->date('due_date')->nullable();
                $table->date('paid_at')->nullable();
                $table->string('payment_method', 40)->nullable();
                $table->boolean('is_recurring')->default(false);
                $table->string('recurrence', 20)->nullable();
                $table->date('recurrence_until')->nullable();
                $table->unsignedBigInteger('recurrence_parent_id')->nullable();
                // Cle de periode (AAAA-MM) : verrou anti-doublon de generation recurrente.
                $table->string('recurrence_period', 7)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('validated_by')->nullable();
                $table->timestamp('validated_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['branch_id', 'expense_date']);
                $table->index(['status', 'expense_date']);
                $table->index('category');
                $table->index('due_date');
                $table->index(['is_recurring', 'recurrence']);
                $table->unique(['recurrence_parent_id', 'recurrence_period'], 'structural_expenses_recurrence_unique');
            });

            $this->addForeign('structural_expenses', 'branch_id', 'branches');
            $this->addForeign('structural_expenses', 'supplier_id', 'finance_suppliers');
            $this->addForeign('structural_expenses', 'recurrence_parent_id', 'structural_expenses');
            $this->addForeign('structural_expenses', 'created_by', 'users');
            $this->addForeign('structural_expenses', 'updated_by', 'users');
            $this->addForeign('structural_expenses', 'validated_by', 'users');
        }

        if (! Schema::hasTable('financial_documents')) {
            Schema::create('financial_documents', function (Blueprint $table) {
                $table->id();
                $table->string('document_type', 40);
                $table->string('status', 20)->default('a_controler');
                $table->string('reference', 120)->nullable();
                $table->date('document_date')->nullable();
                $table->decimal('amount', 12, 2)->nullable();
                $table->string('currency', 8)->default('MAD');
                // Mouvement rattache : ReservationPayment / DepartureCharge / StructuralExpense.
                $table->string('documentable_type', 190)->nullable();
                $table->unsignedBigInteger('documentable_id')->nullable();
                $table->unsignedBigInteger('departure_id')->nullable();
                $table->unsignedBigInteger('voyage_id')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->unsignedBigInteger('supplier_id')->nullable();
                $table->string('client_name', 190)->nullable();
                $table->string('file_path', 255)->nullable();
                $table->string('file_name', 190)->nullable();
                $table->string('file_mime', 100)->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('validated_by')->nullable();
                $table->timestamp('validated_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['documentable_type', 'documentable_id'], 'financial_documents_documentable_index');
                $table->index(['status', 'document_date']);
                $table->index('document_type');
                $table->index('departure_id');
                $table->index('branch_id');
                $table->index('supplier_id');
            });

            $this->addForeign('financial_documents', 'departure_id', 'departures');
            $this->addForeign('financial_documents', 'voyage_id', 'voyages');
            $this->addForeign('financial_documents', 'branch_id', 'branches');
            $this->addForeign('financial_documents', 'supplier_id', 'finance_suppliers');
            $this->addForeign('financial_documents', 'created_by', 'users');
            $this->addForeign('financial_documents', 'updated_by', 'users');
            $this->addForeign('financial_documents', 'validated_by', 'users');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_documents');
        Schema::dropIfExists('structural_expenses');
        Schema::dropIfExists('finance_suppliers');
    }

    /**
     * Ajoute une contrainte seulement si la table cible existe (installations partielles),
     * en nullOnDelete pour ne jamais detruire un mouvement financier en cascade.
     */
    private function addForeign(string $table, string $column, string $references): void
    {
        if (! Schema::hasTable($references)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($column, $references) {
                $blueprint->foreign($column)->references('id')->on($references)->nullOnDelete();
            });
        } catch (\Throwable $e) {
            // Moteur ou schema legacy incompatible : l'index reste, l'integrite est geree applicativement.
        }
    }
};
