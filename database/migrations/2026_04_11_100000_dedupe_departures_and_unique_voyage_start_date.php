<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Une ligne départ par (voyage_id, start_date). Déduplique puis contrainte unique.
 */
return new class extends Migration
{
    public function up(): void
    {
        $conn = 'mysql';

        if (! Schema::connection($conn)->hasTable('departures')) {
            return;
        }

        // Regrouper les doublons (même voyage + même jour de départ)
        $dupes = DB::connection($conn)
            ->table('departures')
            ->select('voyage_id', 'start_date')
            ->selectRaw('COUNT(*) as c')
            ->groupBy('voyage_id', 'start_date')
            ->having('c', '>', 1)
            ->get();

        foreach ($dupes as $row) {
            $rows = DB::connection($conn)
                ->table('departures')
                ->where('voyage_id', $row->voyage_id)
                ->where('start_date', $row->start_date)
                ->orderByRaw('wp_travel_date_id IS NULL ASC')
                ->orderByDesc('wp_travel_date_id')
                ->orderBy('id')
                ->get();

            if ($rows->count() < 2) {
                continue;
            }

            $keep = $rows->first();
            $removeIds = $rows->pluck('id')->slice(1)->values()->all();

            foreach ($removeIds as $rid) {
                $this->repointDepartureFk($conn, (int) $rid, (int) $keep->id);
                DB::connection($conn)->table('departures')->where('id', $rid)->delete();
            }
        }

        // Doublons (voyage_id, wp_travel_date_id) avec wp non null
        $dupesWp = DB::connection($conn)
            ->table('departures')
            ->whereNotNull('wp_travel_date_id')
            ->select('voyage_id', 'wp_travel_date_id')
            ->selectRaw('COUNT(*) as c')
            ->groupBy('voyage_id', 'wp_travel_date_id')
            ->having('c', '>', 1)
            ->get();

        foreach ($dupesWp as $row) {
            $rows = DB::connection($conn)
                ->table('departures')
                ->where('voyage_id', $row->voyage_id)
                ->where('wp_travel_date_id', $row->wp_travel_date_id)
                ->orderBy('id')
                ->get();

            if ($rows->count() < 2) {
                continue;
            }

            $keep = $rows->first();
            $removeIds = $rows->pluck('id')->slice(1)->values()->all();

            foreach ($removeIds as $rid) {
                $this->repointDepartureFk($conn, (int) $rid, (int) $keep->id);
                DB::connection($conn)->table('departures')->where('id', $rid)->delete();
            }
        }

        Schema::connection($conn)->table('departures', function (Blueprint $table) use ($conn) {
            $sm = Schema::connection($conn);
            if (! $this->indexExists($conn, 'departures', 'departures_voyage_start_date_unique')) {
                $table->unique(['voyage_id', 'start_date'], 'departures_voyage_start_date_unique');
            }
            if (! $this->indexExists($conn, 'departures', 'departures_voyage_wp_travel_date_unique')) {
                if ($sm->hasColumn('departures', 'wp_travel_date_id')) {
                    $table->unique(['voyage_id', 'wp_travel_date_id'], 'departures_voyage_wp_travel_date_unique');
                }
            }
        });
    }

    private function repointDepartureFk(string $conn, int $fromId, int $toId): void
    {
        $tables = [
            'departure_hotels' => 'departure_id',
            'reservations' => 'departure_id',
            'stock_movements' => 'departure_id',
        ];

        foreach ($tables as $table => $col) {
            if (! Schema::connection($conn)->hasTable($table)) {
                continue;
            }
            if (! Schema::connection($conn)->hasColumn($table, $col)) {
                continue;
            }
            try {
                DB::connection($conn)->table($table)->where($col, $fromId)->update([$col => $toId]);
            } catch (\Throwable $e) {
                // ignore si table vide / droits
            }
        }
    }

    private function indexExists(string $connection, string $table, string $indexName): bool
    {
        $db = Schema::connection($connection)->getConnection()->getDatabaseName();

        $row = DB::connection($connection)->selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$db, $table, $indexName]
        );

        return $row && (int) ($row->c ?? 0) > 0;
    }

    public function down(): void
    {
        $conn = 'mysql';

        if (! Schema::connection($conn)->hasTable('departures')) {
            return;
        }

        Schema::connection($conn)->table('departures', function (Blueprint $table) {
            $table->dropUnique('departures_voyage_start_date_unique');
        });

        if ($this->indexExists($conn, 'departures', 'departures_voyage_wp_travel_date_unique')) {
            Schema::connection($conn)->table('departures', function (Blueprint $table) {
                $table->dropUnique('departures_voyage_wp_travel_date_unique');
            });
        }
    }
};
