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

        if ($schema->hasTable('departures')) {
            $schema->table('departures', function (Blueprint $table) use ($schema) {
                if (! $schema->hasColumn('departures', 'reserved_capacity')) {
                    $table->unsignedInteger('reserved_capacity')->default(0)->after('total_capacity');
                }
            });
        }

        if ($schema->hasTable('departure_hotel_rooms')) {
            $schema->table('departure_hotel_rooms', function (Blueprint $table) use ($schema) {
                if (! $schema->hasColumn('departure_hotel_rooms', 'total_rooms')) {
                    $table->unsignedInteger('total_rooms')->default(0)->after('room_type');
                }
                if (! $schema->hasColumn('departure_hotel_rooms', 'reserved_rooms')) {
                    $table->unsignedInteger('reserved_rooms')->default(0)->after('total_rooms');
                }
                if (! $schema->hasColumn('departure_hotel_rooms', 'total_places')) {
                    $table->unsignedInteger('total_places')->default(0)->after('reserved_rooms');
                }
                if (! $schema->hasColumn('departure_hotel_rooms', 'reserved_places')) {
                    $table->unsignedInteger('reserved_places')->default(0)->after('total_places');
                }
            });

            $this->backfillDepartureHotelRooms($schema);
        }

        if ($schema->hasTable('reservation_rooms')) {
            $schema->table('reservation_rooms', function (Blueprint $table) use ($schema) {
                if (! $schema->hasColumn('reservation_rooms', 'departure_hotel_id')) {
                    $table->unsignedBigInteger('departure_hotel_id')->nullable()->after('departure_hotel_room_id');
                    $table->index('departure_hotel_id');
                }
                if (! $schema->hasColumn('reservation_rooms', 'room_type_snapshot')) {
                    $table->string('room_type_snapshot', 160)->nullable()->after('tour_hotel_room_id');
                }
                if (! $schema->hasColumn('reservation_rooms', 'passenger_count')) {
                    $table->unsignedInteger('passenger_count')->nullable()->after('room_count');
                }
            });
        }

        if ($schema->hasTable('reservations')) {
            $this->migrateReservationStatuses();
        }

        if (! $schema->hasTable('stock_movements')) {
            $schema->create('stock_movements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('reservation_id')->nullable()->index();
                $table->unsignedBigInteger('departure_id');
                $table->unsignedBigInteger('departure_hotel_room_id');
                $table->string('movement_type', 24);
                $table->integer('rooms_delta');
                $table->integer('places_delta');
                $table->json('before_state')->nullable();
                $table->json('after_state')->nullable();
                $table->string('reason', 500)->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();

                $table->index(['departure_id', 'created_at']);
            });
        }

    }

    private function backfillDepartureHotelRooms(\Illuminate\Database\Schema\Builder $schema): void
    {
        if (! $schema->hasTable('departure_hotel_rooms') || ! $schema->hasTable('reservation_rooms')) {
            return;
        }

        try {
            $cancelledStatuses = ['ANNULEE', 'cancelled', 'expired', 'refunded'];
            $committedStatuses = ['VALIDEE', 'confirmed', 'partially_paid', 'paid'];
            $pendingStatuses = ['EN_COURS', 'pending', 'draft', 'option'];

            $committedAgg = DB::connection('mysql')
                ->table('reservation_rooms as rr')
                ->join('reservations as r', 'r.id', '=', 'rr.reservation_id')
                ->whereNotNull('rr.departure_hotel_room_id')
                ->whereIn('r.status', $committedStatuses)
                ->groupBy('rr.departure_hotel_room_id')
                ->selectRaw('rr.departure_hotel_room_id as dhr_id, SUM(rr.room_count) as rsrv')
                ->pluck('rsrv', 'dhr_id');

            $pendingAgg = DB::connection('mysql')
                ->table('reservation_rooms as rr')
                ->join('reservations as r', 'r.id', '=', 'rr.reservation_id')
                ->whereNotNull('rr.departure_hotel_room_id')
                ->whereIn('r.status', $pendingStatuses)
                ->groupBy('rr.departure_hotel_room_id')
                ->selectRaw('rr.departure_hotel_room_id as dhr_id, SUM(rr.room_count) as rsrv')
                ->pluck('rsrv', 'dhr_id');

            $allActiveAgg = DB::connection('mysql')
                ->table('reservation_rooms as rr')
                ->join('reservations as r', 'r.id', '=', 'rr.reservation_id')
                ->whereNotNull('rr.departure_hotel_room_id')
                ->whereNotIn('r.status', $cancelledStatuses)
                ->groupBy('rr.departure_hotel_room_id')
                ->selectRaw('rr.departure_hotel_room_id as dhr_id, SUM(rr.room_count) as rsrv')
                ->pluck('rsrv', 'dhr_id');

            DB::connection('mysql')->table('departure_hotel_rooms')->orderBy('id')->chunkById(200, function ($rows) use ($committedAgg, $pendingAgg, $allActiveAgg) {
                foreach ($rows as $row) {
                    $id = (int) $row->id;
                    $committed = (int) ($committedAgg[$id] ?? 0);
                    $pending = (int) ($pendingAgg[$id] ?? 0);
                    $allActive = (int) ($allActiveAgg[$id] ?? 0);
                    $cap = max(1, (int) ($row->capacity_total ?? 1));
                    $oldAvailRooms = (int) ($row->available_rooms ?? 0);

                    $totalRooms = max(0, $oldAvailRooms + $allActive);
                    $reservedRooms = $committed;
                    $reservedPendingPlaces = $pending * $cap;
                    $reservedCommittedPlaces = $committed * $cap;
                    $totalPlaces = $totalRooms * $cap;
                    $reservedPlaces = $reservedCommittedPlaces;
                    $availRooms = max(0, $totalRooms - $committed - $pending);
                    $availPlaces = max(0, $totalPlaces - $reservedCommittedPlaces - $reservedPendingPlaces);

                    DB::connection('mysql')->table('departure_hotel_rooms')->where('id', $row->id)->update([
                        'reserved_rooms' => $reservedRooms,
                        'total_rooms' => $totalRooms,
                        'total_places' => $totalPlaces,
                        'reserved_places' => $reservedPlaces,
                        'available_rooms' => $availRooms,
                        'available_places' => $availPlaces,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            //
        }
    }

    private function migrateReservationStatuses(): void
    {
        try {
            DB::connection('mysql')->table('reservations')->where('status', 'EN_COURS')->update(['status' => 'pending']);
            DB::connection('mysql')->table('reservations')->where('status', 'VALIDEE')->update(['status' => 'confirmed']);
            DB::connection('mysql')->table('reservations')->where('status', 'ANNULEE')->update(['status' => 'cancelled']);
        } catch (\Throwable $e) {
            //
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql');

        $schema->dropIfExists('stock_movements');

        if ($schema->hasTable('reservation_rooms')) {
            $schema->table('reservation_rooms', function (Blueprint $table) use ($schema) {
                foreach (['departure_hotel_id', 'room_type_snapshot', 'passenger_count'] as $col) {
                    if ($schema->hasColumn('reservation_rooms', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if ($schema->hasTable('departure_hotel_rooms')) {
            $schema->table('departure_hotel_rooms', function (Blueprint $table) use ($schema) {
                foreach (['total_rooms', 'reserved_rooms', 'total_places', 'reserved_places'] as $col) {
                    if ($schema->hasColumn('departure_hotel_rooms', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if ($schema->hasTable('departures')) {
            $schema->table('departures', function (Blueprint $table) use ($schema) {
                if ($schema->hasColumn('departures', 'reserved_capacity')) {
                    $table->dropColumn('reserved_capacity');
                }
            });
        }
    }
};
