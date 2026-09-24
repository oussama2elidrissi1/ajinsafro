<?php

namespace App\Console\Commands;

use App\Models\Voyage;
use App\Models\VoyageImage;
use App\Models\Wp\WpPost;
use App\Services\WordPressMediaService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Publie dans la médiathèque WordPress les photos rapatriées du catalogue historique.
 *
 * `legacy:import-images` copie les fichiers sur le disque `public` de Laravel et crée les lignes
 * `voyage_images`. Mais le catalogue admin et le front Traveler lisent la vignette dans le meta
 * WordPress `_thumbnail_id`, jamais dans `voyages.featured_image` : tant que les fichiers ne sont
 * pas déclarés comme *attachments*, les fiches importées restent sans image à l'écran.
 *
 * Cette commande copie chaque photo sous `wp-content/uploads/{Y}/{m}/`, crée l'attachment, puis
 * pose la vignette et la galerie du tour.
 *
 * Idempotente : les attachments créés sont mémorisés dans
 * `logistics_meta.legacy_import.wp_attachments` et vérifiés en base avant d'être réutilisés. Un
 * deuxième passage ne duplique rien.
 *
 * La vignette suit la politique prudente de `WordPressMediaService` : une vignette déjà valide
 * est conservée, sauf `--replace-thumbnail`. Rien n'est publié au sens éditorial — le statut des
 * tours n'est pas touché.
 *
 * Dry-run par défaut ; `--execute` applique.
 */
class LegacyPublishImagesToWpCommand extends Command
{
    protected $signature = 'legacy:publish-images-to-wp
        {--execute : Publie réellement (sinon simulation)}
        {--limit=0 : Nombre maximum de fiches traitées}
        {--id=* : Ne traiter que ces identifiants historiques}
        {--replace-thumbnail : Remplace une vignette existante même si elle est valide}';

    protected $description = 'Déclare les photos du catalogue historique comme médias WordPress et pose les vignettes.';

    /** Traveler lit la galerie sous plusieurs clés selon les versions du thème. */
    private const GALLERY_META_KEYS = ['_gallery', 'gallery', 'st_gallery'];

    private const MIME_FOR_EXT = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
    ];

    public function handle(WordPressMediaService $media): int
    {
        $execute = (bool) $this->option('execute');
        $limit = max(0, (int) $this->option('limit'));
        $onlyIds = array_map('intval', (array) $this->option('id'));

        $uploads = $media->getUploadsBasePath();
        if (! is_dir($uploads) || ! is_writable($uploads)) {
            $this->error('Dossier uploads WordPress introuvable ou non inscriptible : '.$uploads);
            $this->line('Cette commande doit tourner sur le serveur, avec `wordpress.uploads_path` configuré.');

            return self::FAILURE;
        }

        $voyages = Voyage::query()
            ->whereNotNull('wp_post_id')
            ->orderBy('id')
            ->get()
            ->filter(fn (Voyage $v) => $v->isLegacyImport())
            ->filter(fn (Voyage $v) => $onlyIds === []
                || in_array($this->legacyId($v), $onlyIds, true))
            ->filter(fn (Voyage $v) => VoyageImage::query()->where('voyage_id', $v->id)->exists());

        if ($voyages->isEmpty()) {
            $this->info('Aucune fiche importée avec des photos à publier.');

            return self::SUCCESS;
        }

        $this->line(sprintf('%d fiche(s) importée(s) avec des photos, uploads : %s', $voyages->count(), $uploads));
        $this->newLine();

        $stats = ['fiches' => 0, 'attachments' => 0, 'reutilises' => 0, 'vignettes' => 0, 'galeries' => 0, 'echecs' => 0];
        $traitees = 0;

        foreach ($voyages as $voyage) {
            if ($limit > 0 && $traitees >= $limit) {
                break;
            }
            $traitees++;
            $stats['fiches']++;

            $legacyId = $this->legacyId($voyage);
            $images = VoyageImage::query()->where('voyage_id', $voyage->id)->orderBy('sort_order')->orderBy('id')->get();

            if (! $execute) {
                $connus = $this->knownAttachments($voyage);
                $aFaire = $images->reject(fn (VoyageImage $i) => isset($connus[$i->path]))->count();
                $this->line(sprintf(
                    '  legacy %-4d %-44s %d photo(s), %d à publier',
                    $legacyId,
                    Str::limit($voyage->slug, 43),
                    $images->count(),
                    $aFaire
                ));
                $stats['attachments'] += $aFaire;

                continue;
            }

            $resultat = $this->publishFor($media, $voyage, $legacyId, $images, $uploads, $stats);

            $this->line(sprintf(
                '  legacy %-4d %-44s %d/%d média(s)%s%s',
                $legacyId,
                Str::limit($voyage->slug, 43),
                count($resultat['ids']),
                $images->count(),
                $resultat['thumbnail'] ? ' · vignette' : '',
                $resultat['gallery'] ? ' · galerie' : ''
            ));

            foreach ($resultat['erreurs'] as $erreur) {
                $this->warn('       '.$erreur);
            }
        }

        $this->newLine();
        $this->info(sprintf(
            '%s : %d média(s) créé(s), %d réutilisé(s), %d vignette(s), %d galerie(s), %d échec(s) sur %d fiche(s).',
            $execute ? 'Publication' : 'Simulation (relancez avec --execute)',
            $stats['attachments'],
            $stats['reutilises'],
            $stats['vignettes'],
            $stats['galeries'],
            $stats['echecs'],
            $stats['fiches']
        ));

        return self::SUCCESS;
    }

    private function legacyId(Voyage $voyage): int
    {
        return (int) data_get($voyage->logistics_meta, 'legacy_import.legacy_id');
    }

    /**
     * Attachments déjà créés pour cette fiche et toujours présents en base.
     *
     * @return array<string, int> chemin Laravel => ID d'attachment
     */
    private function knownAttachments(Voyage $voyage): array
    {
        $map = data_get($voyage->logistics_meta, 'legacy_import.wp_attachments', []);
        if (! is_array($map) || $map === []) {
            return [];
        }

        $ids = array_values(array_filter(array_map('intval', $map)));
        $existants = $ids === []
            ? []
            : WpPost::query()->whereIn('ID', $ids)->where('post_type', 'attachment')->pluck('ID')->map('intval')->all();

        $out = [];
        foreach ($map as $path => $id) {
            if (is_string($path) && in_array((int) $id, $existants, true)) {
                $out[$path] = (int) $id;
            }
        }

        return $out;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, VoyageImage>  $images
     * @param  array<string, int>  $stats
     * @return array{ids: list<int>, thumbnail: bool, gallery: bool, erreurs: list<string>}
     */
    private function publishFor(
        WordPressMediaService $media,
        Voyage $voyage,
        int $legacyId,
        $images,
        string $uploads,
        array &$stats
    ): array {
        $disk = Storage::disk('public');
        $connus = $this->knownAttachments($voyage);
        $erreurs = [];
        $ids = [];
        $idParChemin = [];

        foreach ($images as $image) {
            $path = (string) $image->path;

            if (isset($connus[$path])) {
                $ids[] = $connus[$path];
                $idParChemin[$path] = $connus[$path];
                $stats['reutilises']++;

                continue;
            }

            if (! $disk->exists($path)) {
                $stats['echecs']++;
                $erreurs[] = 'fichier absent : '.$path;

                continue;
            }

            try {
                $relative = $this->copyIntoUploads($media, $disk->get($path), $path, $legacyId, $uploads);
                $attachmentId = $media->createAttachment(
                    $relative,
                    self::MIME_FOR_EXT[strtolower(pathinfo($relative, PATHINFO_EXTENSION))] ?? 'image/jpeg',
                    $media->buildAttachmentPublicUrl($relative),
                    (int) $voyage->wp_post_id
                );
            } catch (\Throwable $e) {
                $stats['echecs']++;
                $erreurs[] = 'média non créé pour '.basename($path).' : '.$e->getMessage();

                continue;
            }

            $ids[] = $attachmentId;
            $idParChemin[$path] = $attachmentId;
            $stats['attachments']++;
        }

        $meta = is_array($voyage->logistics_meta) ? $voyage->logistics_meta : [];
        $meta['legacy_import']['wp_attachments'] = $idParChemin;
        $voyage->logistics_meta = $meta;
        $voyage->save();

        $post = WpPost::find((int) $voyage->wp_post_id);
        if ($post === null) {
            $erreurs[] = 'tour WordPress introuvable : '.$voyage->wp_post_id;

            return ['ids' => $ids, 'thumbnail' => false, 'gallery' => false, 'erreurs' => $erreurs];
        }

        // La couverture Laravel fait foi ; à défaut, la première photo de la galerie.
        $couverture = $idParChemin[(string) $voyage->featured_image] ?? ($ids[0] ?? null);
        $thumbnail = false;

        if ($couverture !== null) {
            $avant = (string) $post->getMeta('_thumbnail_id', '');
            $media->setPostThumbnailIfValidWithPolicy(
                $post,
                $couverture,
                ['source' => 'legacy:publish-images-to-wp', 'legacy_id' => $legacyId],
                (bool) $this->option('replace-thumbnail')
            );
            $thumbnail = (string) $post->getMeta('_thumbnail_id', '') !== $avant;
            $stats['vignettes'] += $thumbnail ? 1 : 0;
        }

        $gallery = false;
        if ($ids !== []) {
            $media->setPostGalleryMetasFiltered($post, $ids, self::GALLERY_META_KEYS, [
                'source' => 'legacy:publish-images-to-wp',
                'legacy_id' => $legacyId,
            ]);
            $gallery = true;
            $stats['galeries']++;
        }

        return ['ids' => $ids, 'thumbnail' => $thumbnail, 'gallery' => $gallery, 'erreurs' => $erreurs];
    }

    /**
     * Copie la photo sous `uploads/{Y}/{m}/` en gardant un nom traçable et sans collision.
     */
    private function copyIntoUploads(
        WordPressMediaService $media,
        string $bytes,
        string $sourcePath,
        int $legacyId,
        string $uploads
    ): string {
        $ym = Carbon::now()->format('Y').'/'.Carbon::now()->format('m');
        $dir = $uploads.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $ym);

        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new \RuntimeException('dossier uploads non créé : '.$dir);
        }

        $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        if (! isset(self::MIME_FOR_EXT[$ext])) {
            throw new \RuntimeException('extension non gérée : '.$ext);
        }

        // Nom parlant : on retrouve le programme d'origine depuis la médiathèque WordPress.
        $base = 'legacy-'.$legacyId.'-'.pathinfo($sourcePath, PATHINFO_FILENAME);
        $candidat = $base.'.'.$ext;
        $n = 1;

        while (is_file($dir.DIRECTORY_SEPARATOR.$candidat)) {
            $candidat = $base.'-'.$n.'.'.$ext;
            $n++;

            if ($n > 999) {
                throw new \RuntimeException('trop de collisions de noms pour '.$base);
            }
        }

        $relative = $ym.'/'.$candidat;

        if (file_put_contents($media->path($relative), $bytes) === false) {
            throw new \RuntimeException('écriture impossible : '.$relative);
        }

        return $relative;
    }
}
