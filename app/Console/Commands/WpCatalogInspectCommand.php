<?php

namespace App\Console\Commands;

use App\Services\WordPressCatalogInspectService;
use Illuminate\Console\Command;

class WpCatalogInspectCommand extends Command
{
    protected $signature = 'wp:catalog-inspect
                            {module : activity|transfer|hotel|voyage}
                            {wp_post_id : WordPress posts.ID (ex. 1483 activité, 14353 transfert)}
                            {--json : Sortie JSON uniquement}';

    protected $description = 'Inspecte directement la base WordPress (connexion wp) : posts, table Traveler, métas, lien catalogue Laravel.';

    public function handle(WordPressCatalogInspectService $inspect): int
    {
        $module = strtolower((string) $this->argument('module'));
        $id = (int) $this->argument('wp_post_id');

        if ($id < 1) {
            $this->error('wp_post_id invalide.');

            return 1;
        }

        try {
            $data = match ($module) {
                'activity', 'activities' => $inspect->inspectActivity($id),
                'transfer', 'transfers' => $inspect->inspectTransfer($id),
                'hotel', 'hotels' => $inspect->inspectHotel($id),
                'voyage', 'voyages', 'tour', 'tours' => $inspect->inspectVoyage($id),
                default => null,
            };
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return 1;
        }

        if ($data === null) {
            $this->error('Module inconnu. Utiliser : activity, transfer, hotel, voyage');

            return 1;
        }

        if ($this->option('json')) {
            $this->line(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return 0;
        }

        $this->info('Connexion WP : '.json_encode($data['wp'], JSON_UNESCAPED_UNICODE));
        $this->newLine();
        if (! empty($data['laravel_catalog']) || ! empty($data['laravel_voyage'])) {
            $this->comment('Laravel (catalogue / voyage lié) :');
            $this->line(json_encode($data['laravel_catalog'] ?? $data['laravel_voyage'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $this->newLine();
        }
        $this->comment('wp_posts (champs affichage titre/slug/statut) :');
        $this->line(json_encode($data['wp_posts'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $this->newLine();

        $detailKey = match ($module) {
            'activity', 'activities' => 'st_activity',
            'transfer', 'transfers' => 'st_cars',
            'hotel', 'hotels' => 'st_hotel',
            default => 'st_tours',
        };
        if (! empty($data[$detailKey])) {
            $this->comment("Table Traveler {$detailKey} :");
            $this->line(json_encode($data[$detailKey], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $this->newLine();
        }
        $this->comment('postmeta (sous-ensemble) :');
        $this->line(json_encode($data['postmeta_subset'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $this->newLine();
        $this->info('Cartographie : config/wordpress_catalog_sources.php');
        $this->info('Option JSON : --json');

        return 0;
    }
}
