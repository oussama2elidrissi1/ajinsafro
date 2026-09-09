<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Refonte commerciale et bilingue (FR / AR) des offres Hajj & Omra.
 *
 * Choix de compatibilite ascendante :
 * les colonnes historiques (`title`, `short_description`, `description`, `booking_conditions`,
 * `required_documents`, `meta_title`, `meta_description`) restent la version FRANCAISE
 * canonique. Aucune donnee n'est deplacee, et l'API publique consommee par WordPress
 * (PublicHajjOmraPackageController) continue de fonctionner sans changement.
 * On ajoute uniquement les colonnes arabes en vis-a-vis, plus les champs commerciaux
 * qui manquaient (ancien prix, remise, ville de depart par date, hebergement detaille,
 * prestations structurees).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hajj_omra_packages', function (Blueprint $table) {
            // --- Traductions arabes (le francais reste dans les colonnes historiques) ---
            if (! Schema::hasColumn('hajj_omra_packages', 'title_ar')) {
                $table->string('title_ar')->nullable()->after('title');
            }
            if (! Schema::hasColumn('hajj_omra_packages', 'short_description_ar')) {
                $table->text('short_description_ar')->nullable()->after('short_description');
            }
            if (! Schema::hasColumn('hajj_omra_packages', 'description_ar')) {
                $table->longText('description_ar')->nullable()->after('description');
            }
            if (! Schema::hasColumn('hajj_omra_packages', 'booking_conditions_ar')) {
                $table->longText('booking_conditions_ar')->nullable()->after('booking_conditions');
            }
            if (! Schema::hasColumn('hajj_omra_packages', 'required_documents_ar')) {
                $table->longText('required_documents_ar')->nullable()->after('required_documents');
            }
            if (! Schema::hasColumn('hajj_omra_packages', 'meta_title_ar')) {
                $table->string('meta_title_ar')->nullable()->after('meta_title');
            }
            if (! Schema::hasColumn('hajj_omra_packages', 'meta_description_ar')) {
                $table->text('meta_description_ar')->nullable()->after('meta_description');
            }

            // --- Argumentaire commercial : prix barre et economie affichee ---
            if (! Schema::hasColumn('hajj_omra_packages', 'old_price')) {
                $table->decimal('old_price', 10, 2)->nullable()->after('adult_price');
            }
            if (! Schema::hasColumn('hajj_omra_packages', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->nullable()->after('old_price');
            }
        });

        Schema::table('hajj_omra_departures', function (Blueprint $table) {
            // Une offre peut partir de plusieurs villes selon la date.
            if (! Schema::hasColumn('hajj_omra_departures', 'departure_city')) {
                $table->string('departure_city', 150)->nullable()->after('return_date');
            }
        });

        Schema::table('hajj_omra_room_prices', function (Blueprint $table) {
            if (! Schema::hasColumn('hajj_omra_room_prices', 'old_price')) {
                $table->decimal('old_price', 10, 2)->nullable()->after('price');
            }
            if (! Schema::hasColumn('hajj_omra_room_prices', 'capacity')) {
                $table->unsignedTinyInteger('capacity')->nullable()->after('old_price');
            }
            if (! Schema::hasColumn('hajj_omra_room_prices', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('stock');
            }
        });

        Schema::table('hajj_omra_program_days', function (Blueprint $table) {
            if (! Schema::hasColumn('hajj_omra_program_days', 'title_ar')) {
                $table->string('title_ar')->nullable()->after('title');
            }
            if (! Schema::hasColumn('hajj_omra_program_days', 'description_ar')) {
                $table->text('description_ar')->nullable()->after('description');
            }
        });

        // --- Hebergement detaille : remplace les 4 champs plats makkah_*/madinah_* ---
        if (! Schema::hasTable('hajj_omra_package_hotels')) {
            Schema::create('hajj_omra_package_hotels', function (Blueprint $table) {
                $table->id();
                $table->foreignId('package_id')->constrained('hajj_omra_packages')->cascadeOnDelete();
                $table->string('city', 40)->default('makkah');
                $table->string('name')->nullable();
                $table->unsignedTinyInteger('stars')->nullable();
                $table->string('haram_distance', 100)->nullable();
                $table->string('location')->nullable();
                $table->unsignedInteger('nights')->nullable();
                $table->string('meal_plan', 40)->nullable();
                $table->text('description')->nullable();
                $table->text('description_ar')->nullable();
                $table->string('image_path')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index(['package_id', 'city']);
                $table->index(['package_id', 'sort_order']);
            });
        }

        // --- Prestations incluses / non incluses, structurees et bilingues ---
        if (! Schema::hasTable('hajj_omra_service_items')) {
            Schema::create('hajj_omra_service_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('package_id')->constrained('hajj_omra_packages')->cascadeOnDelete();
                $table->string('kind', 20)->default('included');
                $table->string('label')->nullable();
                $table->string('label_ar')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index(['package_id', 'kind', 'sort_order']);
            });
        }

        $this->backfill();
    }

    public function down(): void
    {
        Schema::dropIfExists('hajj_omra_service_items');
        Schema::dropIfExists('hajj_omra_package_hotels');

        Schema::table('hajj_omra_program_days', function (Blueprint $table) {
            foreach (['title_ar', 'description_ar'] as $column) {
                if (Schema::hasColumn('hajj_omra_program_days', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('hajj_omra_room_prices', function (Blueprint $table) {
            foreach (['old_price', 'capacity', 'is_active'] as $column) {
                if (Schema::hasColumn('hajj_omra_room_prices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('hajj_omra_departures', function (Blueprint $table) {
            if (Schema::hasColumn('hajj_omra_departures', 'departure_city')) {
                $table->dropColumn('departure_city');
            }
        });

        Schema::table('hajj_omra_packages', function (Blueprint $table) {
            foreach ([
                'title_ar', 'short_description_ar', 'description_ar', 'booking_conditions_ar',
                'required_documents_ar', 'meta_title_ar', 'meta_description_ar',
                'old_price', 'discount_amount',
            ] as $column) {
                if (Schema::hasColumn('hajj_omra_packages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Reprend les donnees deja saisies dans les anciens champs plats, sans les supprimer :
     * les colonnes makkah_*/madinah_* et les JSON included_items/excluded_items restent en
     * place et continuent d'alimenter l'API publique. On les recopie simplement dans les
     * nouvelles structures pour que l'editeur refonte parte des donnees existantes.
     */
    private function backfill(): void
    {
        if (! Schema::hasTable('hajj_omra_packages')) {
            return;
        }

        $packages = DB::table('hajj_omra_packages')->get();
        $now = now();

        foreach ($packages as $package) {
            $hotels = [];

            if (! empty($package->makkah_hotel) || ! empty($package->makkah_haram_distance)) {
                $hotels[] = [
                    'package_id' => $package->id,
                    'city' => 'makkah',
                    'name' => $package->makkah_hotel,
                    'haram_distance' => $package->makkah_haram_distance,
                    'meal_plan' => $package->meal_plan ?? null,
                    'sort_order' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (! empty($package->madinah_hotel) || ! empty($package->madinah_haram_distance)) {
                $hotels[] = [
                    'package_id' => $package->id,
                    'city' => 'madinah',
                    'name' => $package->madinah_hotel,
                    'haram_distance' => $package->madinah_haram_distance,
                    'meal_plan' => $package->meal_plan ?? null,
                    'sort_order' => 2,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($hotels !== [] && DB::table('hajj_omra_package_hotels')->where('package_id', $package->id)->doesntExist()) {
                DB::table('hajj_omra_package_hotels')->insert($hotels);
            }

            if (DB::table('hajj_omra_service_items')->where('package_id', $package->id)->exists()) {
                continue;
            }

            $rows = [];
            foreach (['included_items' => 'included', 'excluded_items' => 'excluded'] as $column => $kind) {
                $decoded = json_decode((string) ($package->{$column} ?? ''), true);

                if (! is_array($decoded)) {
                    continue;
                }

                foreach (array_values($decoded) as $index => $label) {
                    $label = trim((string) $label);
                    if ($label === '') {
                        continue;
                    }

                    $rows[] = [
                        'package_id' => $package->id,
                        'kind' => $kind,
                        'label' => $label,
                        'sort_order' => $index + 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if ($rows !== []) {
                DB::table('hajj_omra_service_items')->insert($rows);
            }
        }
    }
};
