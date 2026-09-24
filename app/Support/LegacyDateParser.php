<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Extrait des dates de départ exploitables depuis les libellés en français du catalogue
 * historique ajinsafro.ma (`dates_depart`).
 *
 * Les libellés sont hétérogènes : « Du 05 au 09 août 2026 », « 19/01/2019 Au 24/01/2019 »,
 * « 07 septembre 2026 (retour 19 septembre) », mais aussi des récurrences (« chaque samedi »)
 * et des points de ramassage (« 22:30 : Départ de Tanger »). Seules les lignes portant une date
 * complète (jour + mois + année) produisent un départ ; le reste est conservé en texte.
 */
final class LegacyDateParser
{
    /** @var array<string, int> */
    private const MONTHS = [
        'janvier' => 1, 'janv' => 1, 'jan' => 1,
        'fevrier' => 2, 'fevr' => 2, 'fev' => 2,
        'mars' => 3,
        'avril' => 4, 'avr' => 4,
        'mai' => 5,
        'juin' => 6,
        'juillet' => 7, 'juil' => 7,
        'aout' => 8,
        'septembre' => 9, 'sept' => 9, 'sep' => 9,
        'octobre' => 10, 'oct' => 10,
        'novembre' => 11, 'nov' => 11,
        'decembre' => 12, 'dec' => 12,
    ];

    /**
     * @return array{start: ?string, end: ?string, raw: string}
     */
    public static function parse(string $label): array
    {
        $raw = trim($label);
        $text = self::normalize($raw);

        // Un horaire de ramassage (« 22:30 : Départ de Tanger ») n'est pas une date de départ.
        if (self::isPickupTime($text)) {
            return ['start' => null, 'end' => null, 'raw' => $raw];
        }

        $dates = self::numericDates($text);
        if ($dates === []) {
            $dates = self::writtenDates($text);
        }

        if ($dates === []) {
            return ['start' => null, 'end' => null, 'raw' => $raw];
        }

        return [
            'start' => $dates[0],
            'end' => $dates[1] ?? null,
            'raw' => $raw,
        ];
    }

    private static function normalize(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = strtr($text, [
            'à' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o',
            'û' => 'u', 'ù' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ]);

        return preg_replace('/\s+/u', ' ', $text) ?? $text;
    }

    /**
     * Une ligne qui ne porte qu'un horaire décrit un point de ramassage, pas une date.
     */
    private static function isPickupTime(string $text): bool
    {
        $hasTime = (bool) preg_match('/\d{1,2}\s*[h:]\s*\d{2}/u', $text);

        return $hasTime && ! preg_match('/\d{4}/u', $text);
    }

    /**
     * Dates au format jj/mm/aaaa (ou jj-mm-aaaa).
     *
     * @return list<string>
     */
    private static function numericDates(string $text): array
    {
        if (! preg_match_all('#(\d{1,2})\s*[/.-]\s*(\d{1,2})\s*[/.-]\s*(\d{4})#u', $text, $m, PREG_SET_ORDER)) {
            return [];
        }

        $out = [];
        foreach ($m as $set) {
            $date = self::build((int) $set[1], (int) $set[2], (int) $set[3]);
            if ($date !== null) {
                $out[] = $date;
            }
        }

        return array_slice($out, 0, 2);
    }

    /**
     * Dates en toutes lettres : « 05 au 09 aout 2026 », « 24 juin au 07 juillet 2026 »,
     * « 29 decembre 2022 au 01 janvier 2023 », « 14 octobre 2018 ».
     *
     * @return list<string>
     */
    private static function writtenDates(string $text): array
    {
        $months = implode('|', array_keys(self::MONTHS));

        // L'année est portée par la dernière mention du libellé : « du 05 au 09 aout 2026 ».
        $fallbackYear = null;
        if (preg_match_all('/\b(19|20)\d{2}\b/u', $text, $ym)) {
            $fallbackYear = (int) end($ym[0]);
        }

        if ($fallbackYear === null) {
            return [];
        }

        // Paires (jour, mois) avec année propre si présente.
        if (! preg_match_all('/\b(\d{1,2})\s*(?:er)?\s+('.$months.')\b\s*(\d{4})?/u', $text, $m, PREG_SET_ORDER)) {
            return [];
        }

        $found = [];
        foreach ($m as $set) {
            $date = self::build((int) $set[1], self::MONTHS[$set[2]], (int) (($set[3] ?? '') ?: $fallbackYear));
            if ($date !== null) {
                $found[] = $date;
            }
        }

        if ($found === []) {
            return [];
        }

        // « du 05 au 09 aout 2026 » : le jour de début précède le mois, sans le répéter.
        if (preg_match('/\b(?:du\s+)?(\d{1,2})\s*(?:er)?\s*(?:au|-)\s*(\d{1,2})\s+('.$months.')\b\s*(\d{4})?/u', $text, $span)) {
            $month = self::MONTHS[$span[3]];
            $year = (int) (($span[4] ?? '') ?: $fallbackYear);
            $start = self::build((int) $span[1], $month, $year);
            $end = self::build((int) $span[2], $month, $year);

            if ($start !== null) {
                return $end !== null ? [$start, $end] : [$start];
            }
        }

        return array_slice($found, 0, 2);
    }

    private static function build(int $day, int $month, int $year): ?string
    {
        if ($day < 1 || $day > 31 || $month < 1 || $month > 12 || $year < 1990 || $year > 2100) {
            return null;
        }

        if (! checkdate($month, $day, $year)) {
            return null;
        }

        return CarbonImmutable::create($year, $month, $day)->toDateString();
    }
}
