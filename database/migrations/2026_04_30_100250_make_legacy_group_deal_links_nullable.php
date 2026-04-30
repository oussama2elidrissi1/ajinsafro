<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE group_deal_pricing_tiers DROP FOREIGN KEY group_deal_pricing_tiers_voyage_id_foreign');
        DB::statement('ALTER TABLE group_deal_pricing_tiers MODIFY voyage_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE group_deal_pricing_tiers ADD CONSTRAINT group_deal_pricing_tiers_voyage_id_foreign FOREIGN KEY (voyage_id) REFERENCES voyages(id) ON DELETE CASCADE');

        DB::statement('ALTER TABLE group_deal_participants DROP FOREIGN KEY group_deal_participants_departure_id_foreign');
        DB::statement('ALTER TABLE group_deal_participants MODIFY departure_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE group_deal_participants ADD CONSTRAINT group_deal_participants_departure_id_foreign FOREIGN KEY (departure_id) REFERENCES departures(id) ON DELETE CASCADE');
        DB::statement("ALTER TABLE group_deal_participants MODIFY status ENUM('pending','confirmed','paid','cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE group_deal_pricing_tiers DROP FOREIGN KEY group_deal_pricing_tiers_voyage_id_foreign');
        DB::statement('ALTER TABLE group_deal_pricing_tiers MODIFY voyage_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE group_deal_pricing_tiers ADD CONSTRAINT group_deal_pricing_tiers_voyage_id_foreign FOREIGN KEY (voyage_id) REFERENCES voyages(id) ON DELETE CASCADE');

        DB::statement('ALTER TABLE group_deal_participants DROP FOREIGN KEY group_deal_participants_departure_id_foreign');
        DB::statement('ALTER TABLE group_deal_participants MODIFY departure_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE group_deal_participants ADD CONSTRAINT group_deal_participants_departure_id_foreign FOREIGN KEY (departure_id) REFERENCES departures(id) ON DELETE CASCADE');
        DB::statement("ALTER TABLE group_deal_participants MODIFY status ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending'");
    }
};
