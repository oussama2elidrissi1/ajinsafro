<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class InvalidateWpCatalogCache extends Command
{
    protected $signature = 'wp:invalidate-cache
                            {type : accommodation-packages | activity-offers | all}
                            {--url= : WordPress base URL (e.g. https://ajinsafro.net)}
                            {--secret= : Invalidate secret defined in wp-config.php}';

    protected $description = 'Invalidate WordPress catalog transients from Laravel';

    public function handle(): int
    {
        $type = $this->argument('type');
        $wpUrl = rtrim((string) $this->option('url', config('app.public_url', 'https://ajinsafro.net')), '/');
        $secret = $this->option('secret', config('app.wp_invalidate_secret', ''));

        if ($secret === '') {
            $this->error('No invalidate secret provided. Set --secret or app.wp_invalidate_secret.');

            return self::FAILURE;
        }

        $keys = [];
        if ($type === 'all' || $type === 'accommodation-packages') {
            $keys[] = 'ajth_accommodation_packages_v1';
        }
        if ($type === 'all' || $type === 'activity-offers') {
            $keys[] = 'ajth_activity_offers_v1';
            $keys[] = 'ajth_activity_filters_v1';
        }

        foreach ($keys as $key) {
            $response = Http::withHeaders([
                'X-Ajth-Secret' => $secret,
            ])->post("{$wpUrl}/wp-json/ajth/v1/invalidate-cache", [
                'key' => $key,
            ]);

            if ($response->successful()) {
                $this->info("Invalidated: {$key}");
            } else {
                $this->warn("Failed to invalidate {$key}: " . $response->body());
            }
        }

        return self::SUCCESS;
    }
}
