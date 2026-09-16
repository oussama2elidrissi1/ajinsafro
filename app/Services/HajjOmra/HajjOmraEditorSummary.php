<?php

namespace App\Services\HajjOmra;

use App\Models\HajjOmraPackage;

/**
 * Etat d'avancement d'une offre dans l'editeur 8 etapes.
 *
 * L'en-tete affiche une progression et une liste « a finaliser » : ce sont des regles
 * metier (ce qui rend une offre publiable), elles restent donc cote Laravel et non
 * reparties dans les gabarits.
 */
class HajjOmraEditorSummary
{
    public const STEPS = [
        'offre' => 'Offre',
        'tarifs' => 'Tarifs',
        'departs' => 'Départs',
        'hebergement' => 'Hébergement',
        'programme' => 'Programme',
        'prestations' => 'Prestations',
        'medias' => 'Médias',
        'publication' => 'Publication',
    ];

    /**
     * @return array<string, array{label: string, done: bool}>
     */
    public function steps(HajjOmraPackage $package): array
    {
        $done = $package->exists ? $this->completion($package) : [];

        $steps = [];
        foreach (self::STEPS as $key => $label) {
            $steps[$key] = ['label' => $label, 'done' => (bool) ($done[$key] ?? false)];
        }

        return $steps;
    }

    /**
     * Points restants, les plus bloquants d'abord. Une entree resolue reste affichee
     * pour que l'utilisateur voie ce qui est deja en place.
     *
     * @param  array<int, string>  $missingArabic
     * @return array<int, array{label: string, done: bool, step: string}>
     */
    public function todos(HajjOmraPackage $package, array $missingArabic = []): array
    {
        if (! $package->exists) {
            return [];
        }

        $done = $this->completion($package);
        $days = $package->programDays->count();
        $duration = (int) ($package->duration_days ?? 0);

        $todos = [
            ['step' => 'medias', 'done' => $done['medias'],
                'label' => $done['medias'] ? 'Image principale en place' : 'Image principale manquante'],
            ['step' => 'programme', 'done' => $done['programme'],
                'label' => $done['programme'] ? 'Programme complet' : max(0, $duration - $days).' jour(s) de programme à renseigner'],
            ['step' => 'tarifs', 'done' => $done['tarifs'],
                'label' => $done['tarifs'] ? 'Tarifs et départs validés' : 'Aucun tarif actif : le prix public restera « sur demande »'],
            ['step' => 'offre', 'done' => $missingArabic === [],
                'label' => $missingArabic === [] ? 'Traductions arabes complètes' : count($missingArabic).' champ(s) sans version arabe'],
        ];

        usort($todos, fn ($a, $b) => ($a['done'] <=> $b['done']));

        return $todos;
    }

    /**
     * @return array<string, bool>
     */
    private function completion(HajjOmraPackage $package): array
    {
        $days = $package->programDays->count();
        $duration = (int) ($package->duration_days ?? 0);

        return [
            'offre' => ($package->title || $package->title_ar) && $package->type && $duration > 0,
            'tarifs' => $package->roomPrices->where('is_active', true)->isNotEmpty(),
            'departs' => $package->departures->isNotEmpty(),
            'hebergement' => $package->hotels->isNotEmpty(),
            'programme' => $days > 0 && $days >= $duration,
            'prestations' => $package->serviceItems->isNotEmpty(),
            'medias' => (string) $package->main_image !== '',
            'publication' => (string) $package->slug !== '' && (string) $package->meta_title !== '',
        ];
    }
}
