<?php

namespace App\Services\Package;

use App\Models\PackageSession;
use App\Models\TravelDayItem;
use App\Models\Voyage;

class PricingService
{
    /**
     * Calculate pricing for a package session.
     *
     * @return array{
     *     base_per_person: int,
     *     options_per_person: int,
     *     total_per_person: int,
     *     total_group: int,
     *     breakdown: array,
     *     delta_last_action: int,
     *     currency: string
     * }
     */
    public function calculate(Voyage $voyage, PackageSession $session, array $selectedItems = []): array
    {
        $basePerPerson = $voyage->price_from ?? 0; // in cents
        $optionsPerPerson = 0;
        $breakdown = [
            'base' => $basePerPerson,
            'included_items' => [],
            'optional_selected' => [],
        ];

        // Calculate selected optional items
        $pricedItemIds = [];
        foreach ($selectedItems as $item) {
            $itemId = (int) ($item['id'] ?? 0);
            if ($itemId > 0 && isset($pricedItemIds[$itemId])) {
                continue;
            }
            if (!$item['included'] && $item['selected']) {
                $delta = $item['price_delta_per_person'] ?? 0;
                $optionsPerPerson += $delta;
                $breakdown['optional_selected'][] = [
                    'id' => $item['id'],
                    'title' => $item['title'],
                    'price_delta' => $delta,
                ];
            }
            if ($itemId > 0) {
                $pricedItemIds[$itemId] = true;
            }
        }

        $totalPerPerson = $basePerPerson + $optionsPerPerson;

        // Calculate group total (considering children pricing if implemented later)
        // For now, simple calculation: adults pay full, children/infants same (adjust later)
        $totalGroup = $totalPerPerson * $session->pax_adults;
        
        // If you want different pricing for children/infants, modify here:
        // $totalGroup += ($totalPerPerson * 0.7) * $session->pax_children; // 70% for children
        // Infants typically free or minimal cost

        return [
            'base_per_person' => $basePerPerson,
            'options_per_person' => $optionsPerPerson,
            'total_per_person' => $totalPerPerson,
            'total_group' => $totalGroup,
            'breakdown' => $breakdown,
            'delta_last_action' => 0, // Will be calculated when action is performed
            'currency' => $session->currency,
        ];
    }

    /**
     * Format price for display.
     */
    public function formatPrice(int $cents, string $currency = 'MAD'): string
    {
        $amount = $cents / 100;
        $symbol = $this->getCurrencySymbol($currency);
        
        return number_format($amount, 2, ',', ' ') . ' ' . $symbol;
    }

    /**
     * Get currency symbol.
     */
    public function getCurrencySymbol(string $currency): string
    {
        return match (strtoupper($currency)) {
            'MAD' => 'DH',
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            default => $currency,
        };
    }

    /**
     * Calculate delta for a specific action (add/remove/modify).
     */
    public function calculateDelta(string $action, TravelDayItem $item, ?array $newOption = null): int
    {
        switch ($action) {
            case 'add':
                return $item->price_delta_per_person;
            
            case 'remove':
                return -$item->price_delta_per_person;
            
            case 'modify':
                if ($newOption && isset($newOption['price_delta'])) {
                    return $newOption['price_delta'] - $item->price_delta_per_person;
                }
                return 0;
            
            default:
                return 0;
        }
    }

    /**
     * Get pricing breakdown with human-readable labels.
     */
    public function getPricingBreakdown(array $pricing): array
    {
        $currency = $pricing['currency'] ?? 'MAD';
        
        return [
            'base' => [
                'label' => 'Prix de base par personne',
                'amount' => $pricing['base_per_person'],
                'formatted' => $this->formatPrice($pricing['base_per_person'], $currency),
            ],
            'options' => [
                'label' => 'Options supplémentaires par personne',
                'amount' => $pricing['options_per_person'],
                'formatted' => $this->formatPrice($pricing['options_per_person'], $currency),
            ],
            'total_per_person' => [
                'label' => 'Total par personne',
                'amount' => $pricing['total_per_person'],
                'formatted' => $this->formatPrice($pricing['total_per_person'], $currency),
            ],
            'total_group' => [
                'label' => sprintf('Total groupe (%d adultes)', $pricing['pax_adults'] ?? 2),
                'amount' => $pricing['total_group'],
                'formatted' => $this->formatPrice($pricing['total_group'], $currency),
            ],
        ];
    }
}
