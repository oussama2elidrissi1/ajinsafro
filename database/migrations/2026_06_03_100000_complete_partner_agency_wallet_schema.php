<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('partners')) {
            Schema::table('partners', function (Blueprint $table) {
                if (! Schema::hasColumn('partners', 'name')) {
                    $table->string('name')->nullable()->after('user_id');
                }
                if (! Schema::hasColumn('partners', 'responsable_name')) {
                    $table->string('responsable_name')->nullable()->after('nom_responsable');
                }
                if (! Schema::hasColumn('partners', 'phone')) {
                    $table->string('phone', 50)->nullable()->after('telephone');
                }
                if (! Schema::hasColumn('partners', 'address')) {
                    $table->string('address', 500)->nullable()->after('adresse');
                }
                if (! Schema::hasColumn('partners', 'city')) {
                    $table->string('city', 100)->nullable()->after('ville');
                }
                if (! Schema::hasColumn('partners', 'logo_path')) {
                    $table->string('logo_path', 500)->nullable()->after('document_path');
                }
                if (! Schema::hasColumn('partners', 'wallet_balance')) {
                    $table->decimal('wallet_balance', 14, 2)->default(0)->after('status');
                }
                if (! Schema::hasColumn('partners', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->after('validated_by');
                }
            });

            DB::table('partners')->whereNull('name')->update(['name' => DB::raw('raison_sociale')]);
            DB::table('partners')->whereNull('responsable_name')->update(['responsable_name' => DB::raw('nom_responsable')]);
            DB::table('partners')->whereNull('phone')->update(['phone' => DB::raw('telephone')]);
            DB::table('partners')->whereNull('address')->update(['address' => DB::raw('adresse')]);
            DB::table('partners')->whereNull('city')->update(['city' => DB::raw('ville')]);

            Schema::table('partners', function (Blueprint $table) {
                if (Schema::hasColumn('partners', 'created_by')) {
                    $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'partner_id')) {
                    $table->unsignedBigInteger('partner_id')->nullable()->after('branch_id');
                }
                if (! Schema::hasColumn('users', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->after('manager_id');
                }
            });

            if (Schema::hasTable('partners') && Schema::hasColumn('users', 'partner_id')) {
                DB::table('partners')
                    ->whereNotNull('user_id')
                    ->orderBy('id')
                    ->get(['id', 'user_id'])
                    ->each(function ($partner): void {
                        DB::table('users')
                            ->where('id', $partner->user_id)
                            ->whereNull('partner_id')
                            ->update(['partner_id' => $partner->id]);
                    });
            }

            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'partner_id')) {
                    $table->foreign('partner_id')->references('id')->on('partners')->nullOnDelete();
                    $table->index('partner_id');
                }
                if (Schema::hasColumn('users', 'created_by')) {
                    $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                    $table->index('created_by');
                }
            });
        }

        if (Schema::hasTable('reservations') && ! Schema::hasColumn('reservations', 'partner_agent_id')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->unsignedBigInteger('partner_agent_id')->nullable()->after('agent_id');
                $table->foreign('partner_agent_id')->references('id')->on('users')->nullOnDelete();
                $table->index('partner_agent_id');
            });
        }

        if (! Schema::hasTable('partner_wallet_transactions')) {
            Schema::create('partner_wallet_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
                $table->string('type', 30);
                $table->decimal('amount', 14, 2);
                $table->string('payment_method', 30)->nullable();
                $table->string('proof_path', 500)->nullable();
                $table->string('status', 30)->default('pending');
                $table->text('note')->nullable();
                $table->text('admin_note')->nullable();
                $table->unsignedBigInteger('requested_by');
                $table->unsignedBigInteger('validated_by')->nullable();
                $table->timestamp('validated_at')->nullable();
                $table->decimal('balance_before', 14, 2)->nullable();
                $table->decimal('balance_after', 14, 2)->nullable();
                $table->unsignedBigInteger('reservation_id')->nullable();
                $table->timestamps();

                $table->foreign('requested_by')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('validated_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('reservation_id')->references('id')->on('reservations')->nullOnDelete();
                $table->index(['partner_id', 'status']);
                $table->index(['type', 'status']);
            });
        }

        if (Schema::hasTable('roles')) {
            $now = now();
            foreach (['partner_admin', 'partner_agent'] as $role) {
                $exists = DB::table('roles')->where('name', $role)->where('guard_name', 'web')->exists();
                if (! $exists) {
                    DB::table('roles')->insert([
                        'name' => $role,
                        'guard_name' => 'web',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_wallet_transactions');

        if (Schema::hasTable('reservations') && Schema::hasColumn('reservations', 'partner_agent_id')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropForeign(['partner_agent_id']);
                $table->dropIndex(['partner_agent_id']);
                $table->dropColumn('partner_agent_id');
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'partner_id')) {
                    $table->dropForeign(['partner_id']);
                    $table->dropIndex(['partner_id']);
                    $table->dropColumn('partner_id');
                }
                if (Schema::hasColumn('users', 'created_by')) {
                    $table->dropForeign(['created_by']);
                    $table->dropIndex(['created_by']);
                    $table->dropColumn('created_by');
                }
            });
        }

        if (Schema::hasTable('partners')) {
            Schema::table('partners', function (Blueprint $table) {
                if (Schema::hasColumn('partners', 'created_by')) {
                    $table->dropForeign(['created_by']);
                }
                foreach (['name', 'responsable_name', 'phone', 'address', 'city', 'logo_path', 'wallet_balance', 'created_by'] as $column) {
                    if (Schema::hasColumn('partners', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
