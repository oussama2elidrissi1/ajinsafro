<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql');

        if ($schema->hasTable('reservation_passengers')) {
            $schema->table('reservation_passengers', function (Blueprint $table) use ($schema) {
                if (! $schema->hasColumn('reservation_passengers', 'gender')) {
                    $table->string('gender', 12)->nullable()->after('type');
                }
                if (! $schema->hasColumn('reservation_passengers', 'traveler_type')) {
                    $table->string('traveler_type', 20)->nullable()->after('gender');
                }
                if (! $schema->hasColumn('reservation_passengers', 'relationship_to_main')) {
                    $table->string('relationship_to_main', 40)->nullable()->after('document_number');
                }
                if (! $schema->hasColumn('reservation_passengers', 'nationality')) {
                    $table->string('nationality', 120)->nullable()->after('relationship_to_main');
                }
                if (! $schema->hasColumn('reservation_passengers', 'phone')) {
                    $table->string('phone', 50)->nullable()->after('nationality');
                }
                if (! $schema->hasColumn('reservation_passengers', 'email')) {
                    $table->string('email', 190)->nullable()->after('phone');
                }
                if (! $schema->hasColumn('reservation_passengers', 'traveler_key')) {
                    $table->string('traveler_key', 80)->nullable()->after('email');
                    $table->index(['reservation_id', 'traveler_key'], 'res_passenger_key_idx');
                }
                if (! $schema->hasColumn('reservation_passengers', 'is_main')) {
                    $table->boolean('is_main')->default(false)->after('traveler_key');
                }
                if (! $schema->hasColumn('reservation_passengers', 'consumes_bed')) {
                    $table->boolean('consumes_bed')->default(true)->after('is_main');
                }
            });
        }

        if ($schema->hasTable('reservations')) {
            $schema->table('reservations', function (Blueprint $table) use ($schema) {
                foreach ([
                    'adults_count',
                    'children_count',
                    'infants_count',
                    'male_count',
                    'female_count',
                ] as $column) {
                    if (! $schema->hasColumn('reservations', $column)) {
                        $table->unsignedInteger($column)->default(0);
                    }
                }
                if (! $schema->hasColumn('reservations', 'rooming_status')) {
                    $table->string('rooming_status', 20)->default('pending')->index();
                }
            });
        }

        if ($schema->hasTable('reservation_dossiers')) {
            $schema->table('reservation_dossiers', function (Blueprint $table) use ($schema) {
                foreach ([
                    'adults_count',
                    'children_count',
                    'infants_count',
                    'male_count',
                    'female_count',
                ] as $column) {
                    if (! $schema->hasColumn('reservation_dossiers', $column)) {
                        $table->unsignedInteger($column)->default(0);
                    }
                }
                if (! $schema->hasColumn('reservation_dossiers', 'rooming_status')) {
                    $table->string('rooming_status', 20)->default('pending')->index();
                }
            });
        }

        if (! $schema->hasTable('reservation_room_allocations')) {
            $schema->create('reservation_room_allocations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('reservation_id')->index();
                $table->unsignedBigInteger('reservation_dossier_id')->nullable()->index();
                $table->unsignedBigInteger('travel_date_id')->default(0)->index();
                $table->unsignedBigInteger('tour_hotel_id')->default(0);
                $table->unsignedBigInteger('tour_hotel_room_id')->default(0)->index();
                $table->unsignedInteger('seats_allocated')->default(0);
                $table->unsignedInteger('rooms_new_count')->default(0);
                $table->unsignedInteger('rooms_total_count')->default(0);
                $table->string('room_source_type', 40)->nullable()->index();
                $table->unsignedBigInteger('room_source_id')->nullable()->index();
                $table->string('room_type', 80)->nullable();
                $table->string('occupancy_mode', 30)->nullable()->index();
                $table->unsignedInteger('capacity')->default(0);
                $table->unsignedInteger('occupied_count')->default(0);
                $table->string('status', 20)->default('pending')->index();
                $table->decimal('supplement_total', 12, 2)->default(0);
                $table->timestamps();
            });
        } else {
            $schema->table('reservation_room_allocations', function (Blueprint $table) use ($schema) {
                if (! $schema->hasColumn('reservation_room_allocations', 'reservation_dossier_id')) {
                    $table->unsignedBigInteger('reservation_dossier_id')->nullable()->after('reservation_id')->index();
                }
                if (! $schema->hasColumn('reservation_room_allocations', 'room_source_type')) {
                    $table->string('room_source_type', 40)->nullable()->after('reservation_dossier_id')->index();
                }
                if (! $schema->hasColumn('reservation_room_allocations', 'room_source_id')) {
                    $table->unsignedBigInteger('room_source_id')->nullable()->after('room_source_type')->index();
                }
                if (! $schema->hasColumn('reservation_room_allocations', 'room_type')) {
                    $table->string('room_type', 80)->nullable()->after('room_source_id');
                }
                if (! $schema->hasColumn('reservation_room_allocations', 'occupancy_mode')) {
                    $table->string('occupancy_mode', 30)->nullable()->after('room_type')->index();
                }
                if (! $schema->hasColumn('reservation_room_allocations', 'capacity')) {
                    $table->unsignedInteger('capacity')->default(0)->after('occupancy_mode');
                }
                if (! $schema->hasColumn('reservation_room_allocations', 'occupied_count')) {
                    $table->unsignedInteger('occupied_count')->default(0)->after('capacity');
                }
                if (! $schema->hasColumn('reservation_room_allocations', 'status')) {
                    $table->string('status', 20)->default('pending')->after('occupied_count')->index();
                }
                if (! $schema->hasColumn('reservation_room_allocations', 'supplement_total')) {
                    $table->decimal('supplement_total', 12, 2)->default(0)->after('status');
                }
            });
        }

        if (! $schema->hasTable('reservation_room_allocation_travelers')) {
            $schema->create('reservation_room_allocation_travelers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('room_allocation_id');
                $table->unsignedBigInteger('traveler_id');
                $table->timestamps();

                $table->unique(['room_allocation_id', 'traveler_id'], 'res_room_alloc_traveler_unique');
                $table->index('traveler_id', 'res_room_alloc_traveler_idx');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql');

        $schema->dropIfExists('reservation_room_allocation_travelers');
    }
};
