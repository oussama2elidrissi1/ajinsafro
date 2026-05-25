<?php

namespace App\Services;

use App\Models\BusinessReferenceValue;
use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

/**
 * Listes métiers pour les formulaires. Source principale : table business_reference_values.
 * L’ancienne clé settings (business_referentials) sert uniquement à une migration ponctuelle si besoin.
 */
final class BusinessReferentialService
{
    public const SETTING_KEY = 'business_referentials';

    /** @var array<string, string> */
    public const GROUP_LABELS = [
        'program_day_types' => 'Types de jour (programme)',
        'theme_types' => 'Types de thème',
        'logistics_transport_types' => 'Types de transport (logistique)',
        'discount_types' => 'Types de réduction',
        'discount_scopes' => 'Portées de réduction',
        'discount_conditions' => 'Conditions de réduction',
        'payment_methods' => 'Moyens de paiement (cases métier)',
        'activity_types' => 'Types d’activité',
        'room_types' => 'Types de chambre',
        'voyage_activity_pricing_types' => 'Types de tarif activité (voyage)',
        'tour_price_by' => 'Tarification par (voyage)',
    ];

    private static function defaults(): array
    {
        return [
            'program_day_types' => [
                ['value' => 'aboard', 'label' => 'À bord'],
                ['value' => 'visite', 'label' => 'Visite'],
                ['value' => 'libre', 'label' => 'Libre'],
            ],
            'theme_types' => [],
            'logistics_transport_types' => [
                ['value' => 'flight', 'label' => 'Vol'],
                ['value' => 'transfer', 'label' => 'Transfert'],
                ['value' => 'train', 'label' => 'Train'],
                ['value' => 'boat', 'label' => 'Bateau'],
                ['value' => 'transport', 'label' => 'Transport'],
            ],
            'discount_types' => [
                ['value' => 'fixed', 'label' => 'Montant fixe'],
                ['value' => 'percent', 'label' => 'Pourcentage'],
            ],
            'discount_scopes' => [
                ['value' => 'global', 'label' => 'Globale'],
                ['value' => 'adult', 'label' => 'Adulte'],
                ['value' => 'child', 'label' => 'Enfant'],
                ['value' => 'infant', 'label' => 'Bébé'],
                ['value' => 'room', 'label' => 'Chambre'],
                ['value' => 'period', 'label' => 'Période'],
            ],
            'discount_conditions' => [
                ['value' => 'none', 'label' => 'Aucune'],
                ['value' => 'date_range', 'label' => 'Période de dates'],
                ['value' => 'min_pax', 'label' => 'Nombre de personnes minimum'],
                ['value' => 'promo_code', 'label' => 'Code promo'],
                ['value' => 'early_booking', 'label' => 'Early booking'],
                ['value' => 'last_minute', 'label' => 'Last minute'],
            ],
            'payment_methods' => [
                ['meta_key' => 'is_meta_payment_gateway_st_paypal', 'label' => 'PayPal'],
                ['meta_key' => 'is_meta_payment_gateway_st_onepay', 'label' => 'OnePay'],
                ['meta_key' => 'is_meta_payment_gateway_st_onepay_atm', 'label' => 'OnePay ATM'],
                ['meta_key' => 'is_meta_payment_gateway_st_payu', 'label' => 'PayU'],
                ['meta_key' => 'is_meta_payment_gateway_st_payulatam', 'label' => 'PayU Latam'],
                ['meta_key' => 'is_meta_payment_gateway_st_payumoney', 'label' => 'PayUmoney'],
                ['meta_key' => 'is_meta_payment_gateway_st_razor', 'label' => 'Razorpay'],
            ],
            'activity_types' => [],
            'room_types' => [],
            'voyage_activity_pricing_types' => [
                ['value' => 'per_person', 'label' => 'Par personne'],
                ['value' => 'fixed', 'label' => 'Fixe'],
            ],
            'tour_price_by' => [
                ['value' => 'person', 'label' => 'Par personne'],
                ['value' => 'group', 'label' => 'Par groupe'],
                ['value' => 'fixed', 'label' => 'Prix fixe'],
                ['value' => 'room', 'label' => 'Par chambre'],
            ],
        ];
    }

    public static function tableAvailable(): bool
    {
        try {
            return Schema::hasTable('business_reference_values');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Structure identique à l’ancien JSON fusionné : clés de groupes => listes.
     */
    public static function allMerged(): array
    {
        $out = [];
        foreach (array_keys(self::defaults()) as $key) {
            $out[$key] = self::groupRowsFromDbOrFallback($key);
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function groupRowsFromDbOrFallback(string $groupKey): array
    {
        if (! self::tableAvailable()) {
            return self::defaults()[$groupKey] ?? [];
        }

        $rows = BusinessReferenceValue::query()
            ->forGroup($groupKey)
            ->active()
            ->ordered()
            ->get();

        if ($rows->isEmpty()) {
            return self::defaults()[$groupKey] ?? [];
        }

        if ($groupKey === 'payment_methods') {
            return $rows->map(function (BusinessReferenceValue $r) {
                $metaKey = $r->meta['meta_key'] ?? $r->value;

                return ['meta_key' => (string) $metaKey, 'label' => $r->label];
            })->values()->all();
        }

        return $rows->map(function (BusinessReferenceValue $r) {
            $row = ['value' => $r->value, 'label' => $r->label];
            if (! empty($r->meta)) {
                $row['meta'] = $r->meta;
            }

            return $row;
        })->values()->all();
    }

    /**
     * @return list<array{meta_key: string, label: string}>
     */
    public static function paymentMethods(): array
    {
        $all = self::allMerged();
        $methods = $all['payment_methods'] ?? [];
        if (! is_array($methods) || $methods === []) {
            return self::normalizePaymentMethods(self::defaults()['payment_methods']);
        }

        return self::normalizePaymentMethods($methods);
    }

    /**
     * Catalogue "technique" des moyens de paiement connus (valeurs par défaut).
     * Utile pour proposer une liste simple côté admin sans demander de JSON.
     *
     * @return list<array{meta_key: string, label: string}>
     */
    public static function defaultPaymentMethodsCatalog(): array
    {
        return self::normalizePaymentMethods(self::defaults()['payment_methods']);
    }

    /**
     * @param  list<array<string, mixed>>  $methods
     * @return list<array{meta_key: string, label: string}>
     */
    private static function normalizePaymentMethods(array $methods): array
    {
        return array_values(array_filter(array_map(function ($row) {
            if (! is_array($row)) {
                return null;
            }
            $key = trim((string) ($row['meta_key'] ?? ''));
            if ($key === '') {
                return null;
            }

            return ['meta_key' => $key, 'label' => (string) ($row['label'] ?? $key)];
        }, $methods)));
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function programDayTypes(): array
    {
        $rows = self::allMerged()['program_day_types'] ?? [];

        return self::normalizeValueLabelList(is_array($rows) ? $rows : [], self::defaults()['program_day_types']);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function voyageActivityPricingTypes(): array
    {
        $rows = self::allMerged()['voyage_activity_pricing_types'] ?? [];

        return self::normalizeValueLabelList(is_array($rows) ? $rows : [], self::defaults()['voyage_activity_pricing_types']);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function tourPriceByOptions(): array
    {
        $rows = self::allMerged()['tour_price_by'] ?? [];

        return self::normalizeValueLabelList(is_array($rows) ? $rows : [], self::defaults()['tour_price_by']);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function logisticsTransportTypes(): array
    {
        $rows = self::allMerged()['logistics_transport_types'] ?? [];

        return self::normalizeValueLabelList(is_array($rows) ? $rows : [], self::defaults()['logistics_transport_types']);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<array<string, string>>  $fallback
     * @return list<array{value: string, label: string}>
     */
    private static function normalizeValueLabelList(array $rows, array $fallback): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $v = trim((string) ($row['value'] ?? ''));
            if ($v === '') {
                continue;
            }
            $out[] = ['value' => $v, 'label' => (string) ($row['label'] ?? $v)];
        }

        if ($out !== []) {
            return $out;
        }
        if ($fallback === []) {
            return [];
        }

        return self::normalizeValueLabelList($fallback, []);
    }

    /**
     * Import ponctuel depuis l’ancien JSON settings (migration manuelle / commande).
     */
    public static function importFromLegacySettingJson(): int
    {
        if (! self::tableAvailable()) {
            return 0;
        }

        $raw = Setting::getValue(self::SETTING_KEY, null);
        $decoded = json_decode((string) $raw, true);
        if (! is_array($decoded)) {
            return 0;
        }

        $count = 0;
        foreach ($decoded as $groupKey => $items) {
            if (! is_array($items) || !isset(self::defaults()[$groupKey])) {
                continue;
            }
            foreach ($items as $i => $item) {
                if (! is_array($item)) {
                    continue;
                }
                if ($groupKey === 'payment_methods') {
                    $metaKey = trim((string) ($item['meta_key'] ?? ''));
                    if ($metaKey === '') {
                        continue;
                    }
                    BusinessReferenceValue::query()->updateOrCreate(
                        ['group_key' => $groupKey, 'value' => $metaKey],
                        [
                            'label' => (string) ($item['label'] ?? $metaKey),
                            'is_active' => true,
                            'sort_order' => (int) $i,
                            'meta' => ['meta_key' => $metaKey],
                        ]
                    );
                } else {
                    $v = trim((string) ($item['value'] ?? ''));
                    if ($v === '') {
                        continue;
                    }
                    BusinessReferenceValue::query()->updateOrCreate(
                        ['group_key' => $groupKey, 'value' => $v],
                        [
                            'label' => (string) ($item['label'] ?? $v),
                            'is_active' => true,
                            'sort_order' => (int) $i,
                            'meta' => isset($item['meta']) && is_array($item['meta']) ? $item['meta'] : null,
                        ]
                    );
                }
                $count++;
            }
        }

        return $count;
    }
}
