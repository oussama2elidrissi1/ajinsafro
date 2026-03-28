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
            $schema->table('reservations', function (Blueprint $table) {
                if (! Schema::hasColumn('reservations', 'prestation_type')) {
                    $table->string('prestation_type', 30)->nullable()->after('tour_id')
                        ->comment('package|vol|hebergement (module workspace)');
                }
                if (! Schema::hasColumn('reservations', 'paid_amount')) {
                    $table->decimal('paid_amount', 12, 2)->nullable()->after('base_price')
                        ->comment('Acompte / montant payé (workspace)');
                }
            });
        }

        if (! $schema->hasTable('reservation_extras')) {
            $schema->create('reservation_extras', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('reservation_id');
                $table->string('name', 255);
                $table->decimal('price', 12, 2)->default(0);
                $table->string('passenger_key', 64)->nullable()->comment('titulaire, comp_0, etc.');
                $table->timestamps();

                $table->foreign('reservation_id')
                    ->references('id')->on('reservations')
                    ->onDelete('cascade');
                $table->index('reservation_id');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql');
        $schema->dropIfExists('reservation_extras');

        if ($schema->hasTable('reservations')) {
            $schema->table('reservations', function (Blueprint $table) {
                if (Schema::hasColumn('reservations', 'prestation_type')) {
                    $table->dropColumn('prestation_type');
                }
                if (Schema::hasColumn('reservations', 'paid_amount')) {
                    $table->dropColumn('paid_amount');
                }
            });
        }
    }
};
