<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WpSearchMetaCommand extends Command
{
    protected $signature = 'wp:search-meta
                            {needle : Chaîne à chercher (ex: 2026/04/3d67d024-z)}
                            {--limit=50 : Nombre max de résultats}
                            {--json : Sortie JSON uniquement}';

    protected $description = 'Recherche une chaîne dans wp_postmeta.meta_value (connexion wp). Utile pour retrouver une URL média injectée en base.';

    public function handle(): int
    {
        $needle = (string) $this->argument('needle');
        $limit = (int) $this->option('limit');
        if ($limit < 1) {
            $limit = 50;
        }
        if ($limit > 500) {
            $limit = 500;
        }

        $rows = DB::connection('wp')->table('postmeta')
            ->select(['post_id', 'meta_key', 'meta_value'])
            ->where('meta_value', 'like', '%'.$needle.'%')
            ->limit($limit)
            ->get()
            ->map(function ($r) use ($needle) {
                $value = (string) $r->meta_value;
                $pos = stripos($value, $needle);
                $snippet = $value;
                if ($pos !== false) {
                    $start = max(0, $pos - 60);
                    $snippet = substr($value, $start, 180);
                }

                return [
                    'post_id' => (int) $r->post_id,
                    'meta_key' => (string) $r->meta_key,
                    'snippet' => $snippet,
                ];
            })
            ->all();

        if ($this->option('json')) {
            $this->line(json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return 0;
        }

        if (empty($rows)) {
            $this->info('Aucun résultat.');

            return 0;
        }

        $this->info('Résultats: '.count($rows));
        foreach ($rows as $row) {
            $this->line(sprintf(
                'post_id=%d meta_key=%s snippet=%s',
                $row['post_id'],
                $row['meta_key'],
                $row['snippet']
            ));
        }

        return 0;
    }
}

