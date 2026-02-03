<?php

namespace App\Services\Package;

use App\DTOs\PackageState;
use App\Models\PackageSession;
use App\Models\Voyage;

class PackageStateBuilder
{
    public function __construct(
        protected PricingService $pricingService
    ) {}

    /**
     * Build complete package state for a voyage and session.
     */
    public function build(Voyage $voyage, PackageSession $session): PackageState
    {
        $voyage->load(['programDays', 'dayItems', 'images']);

        // Tour info
        $tour = [
            'id' => $voyage->id,
            'name' => $voyage->name,
            'slug' => $voyage->slug,
            'destination' => $voyage->destination,
            'duration_text' => $voyage->duration_text,
            'total_days' => $voyage->programDays->count(),
            'total_nights' => $voyage->programDays->sum('nights'),
            'featured_image' => $voyage->featured_image_url,
            'gallery' => $voyage->images->map(fn($img) => [
                'id' => $img->id,
                'url' => $img->url,
                'sort_order' => $img->sort_order,
            ])->toArray(),
        ];

        // Session info
        $sessionData = [
            'id' => $session->id,
            'pax_adults' => $session->pax_adults,
            'pax_children' => $session->pax_children,
            'pax_infants' => $session->pax_infants,
            'total_pax' => $session->total_pax,
            'currency' => $session->currency,
            'expires_at' => $session->expires_at?->toIso8601String(),
            'state' => $session->state,
        ];

        // Build days with items
        $days = [];
        foreach ($voyage->programDays as $programDay) {
            $items = $this->buildDayItems($programDay->day_number, $voyage, $session);
            
            $days[] = [
                'day_number' => $programDay->day_number,
                'title' => $programDay->title,
                'city' => $programDay->city,
                'day_type' => $programDay->day_type,
                'day_label' => $programDay->day_label,
                'nights' => $programDay->nights,
                'meals' => [
                    'breakfast' => $programDay->hasMealBreakfast(),
                    'lunch' => $programDay->hasMealLunch(),
                    'dinner' => $programDay->hasMealDinner(),
                ],
                'description' => $programDay->description,
                'content_html' => $programDay->content_html,
                'items' => $items,
            ];
        }

        // Calculate included counters
        $includedCounters = $this->calculateIncludedCounters($voyage, $session);

        // Collect all selected items for pricing
        $selectedItems = $this->collectSelectedItems($days, $session);

        // Calculate pricing
        $pricing = $this->pricingService->calculate($voyage, $session, $selectedItems);
        $pricing['pax_adults'] = $session->pax_adults;
        $pricing['pax_children'] = $session->pax_children;
        $pricing['pax_infants'] = $session->pax_infants;

        // Catalog (stub for now - will be used for adding new items)
        $catalog = [
            'available_flights' => [],
            'available_hotels' => [],
            'available_activities' => [],
            'available_transfers' => [],
        ];

        return new PackageState(
            tour: $tour,
            session: $sessionData,
            includedCounters: $includedCounters,
            pricing: $pricing,
            days: $days,
            catalog: $catalog
        );
    }

    /**
     * Build items for a specific day.
     */
    protected function buildDayItems(int $dayNumber, Voyage $voyage, PackageSession $session): array
    {
        $state = $session->state;
        $removedIds = $state['removed_items'] ?? [];
        $modifiedItems = $state['modified_items'] ?? [];

        $items = $voyage->dayItems()
            ->where(function ($query) use ($dayNumber) {
                $query->where('day_number', $dayNumber)
                    ->orWhere(function ($q) use ($dayNumber) {
                        // Include multi-day items that span this day
                        $q->where('start_day', '<=', $dayNumber)
                          ->where(function ($q2) use ($dayNumber) {
                              $q2->whereNull('end_day')
                                 ->orWhere('end_day', '>=', $dayNumber);
                          });
                    });
            })
            ->get();

        $result = [];
        foreach ($items as $item) {
            // Skip removed items
            if (in_array($item->id, $removedIds)) {
                continue;
            }

            // Check if modified
            $isModified = isset($modifiedItems[$item->id]);
            $activeOption = $isModified ? $modifiedItems[$item->id] : null;

            $itemData = [
                'id' => $item->id,
                'type' => $item->type,
                'type_label' => $item->type_label,
                'title' => $item->title,
                'details' => $item->details,
                'included' => $item->included,
                'selected' => $item->included, // Initially, included items are selected
                'price_delta_per_person' => $item->price_delta_per_person,
                'formatted_price' => $item->formatted_price_delta,
                'start_day' => $item->start_day,
                'end_day' => $item->end_day,
                'nights' => $item->nights,
                'is_multi_day' => $item->isMultiDay(),
                'duration_days' => $item->duration_days,
                'meta' => $item->meta_json,
                'sort_order' => $item->sort_order,
            ];

            // Add options if available
            if ($item->options_json) {
                $itemData['options'] = $item->options_json;
                $itemData['active_option'] = $activeOption;
            }

            $result[] = $itemData;
        }

        // Add "added" items from state for this day
        $addedItems = $state['added_items'] ?? [];
        foreach ($addedItems as $added) {
            if (($added['day_number'] ?? null) === $dayNumber) {
                $result[] = array_merge($added, [
                    'is_added' => true,
                    'selected' => true,
                ]);
            }
        }

        // Sort by sort_order
        usort($result, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

        return $result;
    }

    /**
     * Calculate included/optional counters by type.
     */
    protected function calculateIncludedCounters(Voyage $voyage, PackageSession $session): array
    {
        $state = $session->state;
        $removedIds = $state['removed_items'] ?? [];

        $counters = [
            'flight' => ['included' => 0, 'optional' => 0, 'selected' => 0],
            'hotel_stay' => ['included' => 0, 'optional' => 0, 'selected' => 0],
            'transfer' => ['included' => 0, 'optional' => 0, 'selected' => 0],
            'activity' => ['included' => 0, 'optional' => 0, 'selected' => 0],
            'meal' => ['included' => 0, 'optional' => 0, 'selected' => 0],
            'addon' => ['included' => 0, 'optional' => 0, 'selected' => 0],
        ];

        $items = $voyage->dayItems;

        foreach ($items as $item) {
            // Skip removed items
            if (in_array($item->id, $removedIds)) {
                continue;
            }

            $type = $item->type;
            if (!isset($counters[$type])) {
                continue;
            }

            if ($item->included) {
                $counters[$type]['included']++;
                $counters[$type]['selected']++;
            } else {
                $counters[$type]['optional']++;
            }
        }

        // Add added items from state
        $addedItems = $state['added_items'] ?? [];
        foreach ($addedItems as $added) {
            $type = $added['type'] ?? null;
            if ($type && isset($counters[$type])) {
                $counters[$type]['selected']++;
            }
        }

        return $counters;
    }

    /**
     * Collect all selected items across all days.
     */
    protected function collectSelectedItems(array $days, PackageSession $session): array
    {
        $selected = [];
        
        foreach ($days as $day) {
            foreach ($day['items'] as $item) {
                if ($item['selected'] ?? false) {
                    $selected[] = $item;
                }
            }
        }

        return $selected;
    }

    /**
     * Refresh package state after an action.
     */
    public function refresh(Voyage $voyage, PackageSession $session): PackageState
    {
        return $this->build($voyage, $session);
    }
}
