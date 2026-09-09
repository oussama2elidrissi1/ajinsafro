<?php

namespace App\Support\Locale;

use Illuminate\Support\Facades\App;

/**
 * Champs bilingues FR / AR.
 *
 * Convention du projet : la colonne historique porte le FRANCAIS (`title`), la colonne
 * suffixee porte l'ARABE (`title_ar`). Rien n'est deplace en base, ce qui garantit que
 * les integrations existantes (API publique consommee par WordPress) restent intactes.
 *
 * Le francais fait toujours office de repli : une offre dont la traduction arabe n'est pas
 * saisie reste affichable en arabe, avec le texte francais, plutot qu'un blanc.
 */
trait HasBilingualFields
{
    /**
     * Champs disposant d'une colonne `_ar`. A redefinir dans chaque modele.
     *
     * @return list<string>
     */
    public function bilingualFields(): array
    {
        return property_exists($this, 'bilingual') ? $this->bilingual : [];
    }

    /**
     * Valeur dans la locale demandee, avec repli sur le francais.
     */
    public function localized(string $field, ?string $locale = null): ?string
    {
        $locale = $locale ?: App::getLocale();

        if ($this->isArabic($locale)) {
            $arabic = $this->{$field.'_ar'} ?? null;

            if (is_string($arabic) && trim($arabic) !== '') {
                return $arabic;
            }
        }

        $french = $this->{$field} ?? null;

        return is_string($french) && trim($french) !== '' ? $french : null;
    }

    /**
     * Vrai si la version arabe du champ est reellement saisie.
     */
    public function hasArabic(string $field): bool
    {
        $value = $this->{$field.'_ar'} ?? null;

        return is_string($value) && trim($value) !== '';
    }

    /**
     * Champs bilingues dont la traduction arabe manque, pour l'alerte
     * « Traduction arabe non completee » de l'editeur.
     *
     * Seuls les champs dont le francais est renseigne sont signales : inutile de
     * reclamer une traduction pour un champ vide des deux cotes.
     *
     * @return list<string>
     */
    public function missingArabicFields(): array
    {
        $missing = [];

        foreach ($this->bilingualFields() as $field) {
            $french = $this->{$field} ?? null;
            $hasFrench = is_string($french) && trim($french) !== '';

            if ($hasFrench && ! $this->hasArabic($field)) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    public function isArabicComplete(): bool
    {
        return $this->missingArabicFields() === [];
    }

    private function isArabic(string $locale): bool
    {
        return str_starts_with(strtolower($locale), 'ar');
    }
}
