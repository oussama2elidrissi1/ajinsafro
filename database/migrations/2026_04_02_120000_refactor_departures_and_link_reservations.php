<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql');

        // 1) Expand departures to match "sellable instance" requirements.
        if ($schema->hasTable('departures')) {
            $schema->table('departures', function (Blueprint $table) use ($schema) {
                if (! $schema->hasColumn('departures', 'end_date')) {
                    $table->date('end_date')->nullable()->after('start_date');
                }
                if (! $schema->hasColumn('departures', 'total_capacity')) {
                    $table->unsignedInteger('total_capacity')->default(0)->after('status');
                }
                if (! $schema->hasColumn('departures', 'available_capacity')) {
                    $table->unsignedInteger('available_capacity')->default(0)->after('total_capacity');
                }
                if (! $schema->hasColumn('departures', 'base_price')) {
                    $table->decimal('base_price', 12, 2)->nullable()->after('available_capacity');
                }
                if (! $schema->hasColumn('departures', 'sale_price')) {
                    $table->decimal('sale_price', 12, 2)->nullable()->after('base_price');
                }
                if (! $schema->hasColumn('departures', 'notes')) {
                    $table->text('notes')->nullable()->after('sale_price');
                }
                // Backward-compat bridge: link to legacy WP travel date id when migrated.
                if (! $schema->hasColumn('departures', 'wp_travel_date_id')) {
                    $table->unsignedBigInteger('wp_travel_date_id')->nullable()->after('voyage_id')
                        ->comment('Legacy mapping to wp.aj_travel_dates.id');
                    $table->index(['voyage_id', 'wp_travel_date_id'], 'departures_voyage_wp_travel_date_idx');
                }
            });
        }

        // 2) Create departure_hotels (departure-specific hotel assignments).
        if (! $schema->hasTable('departure_hotels')) {
            $schema->create('departure_hotels', function (Blueprint $table) {
                $table->id();
                $table->foreignId('departure_id')->constrained('departures')->cascadeOnDelete();

                // Preferred: Laravel hotels module.
                $table->foreignId('hotel_id')->nullable()->constrained('hotels')->nullOnDelete();

                // Snapshot / fallback (for legacy WP tour hotels that don't map to Hotel model).
                $table->string('hotel_name', 255)->nullable();
                $table->unsignedTinyInteger('stars')->nullable();
                $table->string('address', 500)->nullable();

                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['departure_id', 'is_active']);
            });
        }

        // 3) Create departure_hotel_rooms (departure-specific room stock/pricing).
        if (! $schema->hasTable('departure_hotel_rooms')) {
            $schema->create('departure_hotel_rooms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('departure_hotel_id')->constrained('departure_hotels')->cascadeOnDelete();

                // Preferred: Laravel hotels module room type.
                $table->unsignedBigInteger('room_id')->nullable()->comment('hotels_module: hotel_room_types.id (optional)');

                // Snapshot / fallback.
                $table->string('room_type', 120)->nullable();

                $table->unsignedInteger('capacity_total')->default(0);
                $table->unsignedInteger('available_rooms')->default(0);
                $table->unsignedInteger('available_places')->default(0);
                $table->decimal('supplement', 12, 2)->default(0);
                $table->string('status', 20)->default('available'); // available, limited, full, closed
                $table->timestamps();

                $table->index(['departure_hotel_id', 'status']);
            });
        }

        // 4) Update reservations: link to departure (new source of truth).
        if ($schema->hasTable('reservations')) {
            $schema->table('reservations', function (Blueprint $table) use ($schema) {
                if (! $schema->hasColumn('reservations', 'departure_id')) {
                    $table->unsignedBigInteger('departure_id')->nullable()->after('travel_date_id');
                    $table->index('departure_id');
                }
                // Optional quick reference (Laravel voyage id).
                if (! $schema->hasColumn('reservations', 'voyage_id')) {
                    $table->unsignedBigInteger('voyage_id')->nullable()->after('departure_id');
                    $table->index('voyage_id');
                }
                // Requested naming; keep legacy created_by too.
                if (! $schema->hasColumn('reservations', 'created_by_user_id')) {
                    $table->unsignedBigInteger('created_by_user_id')->nullable()->after('created_by');
                    $table->index('created_by_user_id');
                }
            });

            // Best-effort backfill: created_by_user_id <= created_by (existing column).
            try {
                DB::connection('mysql')->statement('UPDATE reservations SET created_by_user_id = created_by WHERE created_by_user_id IS NULL AND created_by IS NOT NULL');
            } catch (\Throwable $e) {
                // keep migration idempotent across DB engines
            }
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql');

        if ($schema->hasTable('reservations')) {
            $schema->table('reservations', function (Blueprint $table) use ($schema) {
                if ($schema->hasColumn('reservations', 'created_by_user_id')) {
                    $table->dropColumn('created_by_user_id');
                }
                if ($schema->hasColumn('reservations', 'voyage_id')) {
                    $table->dropColumn('voyage_id');
                }
                if ($schema->hasColumn('reservations', 'departure_id')) {
                    $table->dropColumn('departure_id');
                }
            });
        }

        $schema->dropIfExists('departure_hotel_rooms');
        $schema->dropIfExists('departure_hotels');

        if ($schema->hasTable('departures')) {
            $schema->table('departures', function (Blueprint $table) use ($schema) {
                if ($schema->hasColumn('departures', 'wp_travel_date_id')) {
                    $table->dropColumn('wp_travel_date_id');
                }
                if ($schema->hasColumn('departures', 'notes')) {
                    $table->dropColumn('notes');
                }
                if ($schema->hasColumn('departures', 'sale_price')) {
                    $table->dropColumn('sale_price');
                }
                if ($schema->hasColumn('departures', 'base_price')) {
                    $table->dropColumn('base_price');
                }
                if ($schema->hasColumn('departures', 'available_capacity')) {
                    $table->dropColumn('available_capacity');
                }
                if ($schema->hasColumn('departures', 'total_capacity')) {
                    $table->dropColumn('total_capacity');
                }
                if ($schema->hasColumn('departures', 'end_date')) {
                    $table->dropColumn('end_date');
                }
            });
        }
    }
};

