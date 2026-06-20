<?php

namespace App\Services;

use App\Models\CustomRequestQuote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CustomRequestQuotationService
{
    public function ensureProgramDays(CustomRequestQuote $quote): void
    {
        $quote->loadMissing('customRequest');
        $customRequest = $quote->customRequest;

        if (! $customRequest || ! $customRequest->desired_departure_date) {
            return;
        }

        $nights = $this->resolveNights($customRequest);
        if ($nights < 1) {
            return;
        }

        DB::transaction(function () use ($quote, $customRequest, $nights): void {
            $startDate = Carbon::parse($customRequest->desired_departure_date);
            $existing = $quote->days()->get()->keyBy('day_number');

            for ($dayNumber = 1; $dayNumber <= $nights; $dayNumber++) {
                if ($existing->has($dayNumber)) {
                    continue;
                }

                $quote->days()->create([
                    'day_number' => $dayNumber,
                    'date' => $startDate->copy()->addDays($dayNumber - 1)->toDateString(),
                    'title' => 'Jour '.$dayNumber,
                    'city' => $customRequest->desired_destination,
                    'client_description' => null,
                    'internal_notes' => null,
                    'sort_order' => $dayNumber - 1,
                ]);
            }
        });
    }

    public function save(CustomRequestQuote $quote, array $data, User $user): void
    {
        DB::transaction(function () use ($quote, $data, $user): void {
            $quote->update([
                'offline_agent_id' => $quote->offline_agent_id ?: $user->id,
                'supplier_name' => $data['supplier_name'] ?? null,
                'valid_until' => $data['valid_until'] ?? null,
                'response_deadline' => $data['response_deadline'] ?? null,
                'currency' => $data['currency'] ?? $quote->currency ?? 'MAD',
                'requested_deposit' => $this->number($data['requested_deposit'] ?? null),
                'paid_amount' => $this->number($data['paid_amount'] ?? 0),
                'customer_conditions' => $data['customer_conditions'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
            ]);

            if (array_key_exists('days', $data)) {
                $this->syncProgram($quote, $data['days'] ?? [], ! array_key_exists('items', $data));
            }

            if (array_key_exists('items', $data)) {
                $this->syncAssignedItems($quote, $data['items'] ?? []);
            }

            $quote->calculateTotals();

            if ((float) $quote->paid_amount > (float) $quote->total_sale) {
                abort(422, 'Le montant payé ne peut pas dépasser le total de vente.');
            }
        });
    }

    private function syncProgram(CustomRequestQuote $quote, array $days, bool $syncNestedServices = true): void
    {
        if ($syncNestedServices) {
            $quote->items()->delete();
        }

        foreach (array_values($days) as $dayIndex => $dayData) {
            $dayNumber = (int) ($dayData['day_number'] ?? ($dayIndex + 1));
            $day = null;

            if (! empty($dayData['id'])) {
                $day = $quote->days()->whereKey($dayData['id'])->first();
            }

            $day ??= $quote->days()->firstOrNew(['day_number' => $dayNumber]);
            $day->fill([
                'day_number' => $dayNumber,
                'date' => $dayData['date'] ?? null,
                'title' => $dayData['title'] ?? null,
                'city' => $dayData['city'] ?? null,
                'client_description' => $dayData['client_description'] ?? null,
                'internal_notes' => $dayData['internal_notes'] ?? null,
                'sort_order' => (int) ($dayData['sort_order'] ?? $dayIndex),
            ]);
            $day->save();

            if ($syncNestedServices) {
                foreach (array_values($dayData['services'] ?? []) as $serviceIndex => $serviceData) {
                    $payload = $this->servicePayload($serviceData, $serviceIndex);
                    $payload['custom_request_quote_day_id'] = $day->id;
                    $quote->items()->create($payload);
                }
            }
        }
    }

    private function syncAssignedItems(CustomRequestQuote $quote, array $items): void
    {
        $quote->items()->delete();
        $days = $quote->days()->orderBy('sort_order')->orderBy('day_number')->get()->values();
        $validDayIds = $days->pluck('id')->map(fn ($id) => (int) $id)->all();

        foreach (array_values($items) as $index => $item) {
            $payload = $this->servicePayload($item, $index);
            $dayId = (int) ($item['custom_request_quote_day_id'] ?? $item['quotation_day_id'] ?? $item['day_id'] ?? 0);

            if ($dayId <= 0 && isset($item['day_index'])) {
                $dayId = (int) ($days->get((int) $item['day_index'])?->id ?? 0);
            }

            if ($dayId > 0 && in_array($dayId, $validDayIds, true)) {
                $payload['custom_request_quote_day_id'] = $dayId;
            }

            $quote->items()->create($payload);
        }
    }

    private function syncLegacyItems(CustomRequestQuote $quote, array $items): void
    {
        $quote->items()->delete();

        foreach (array_values($items) as $index => $item) {
            $quote->items()->create($this->servicePayload($item, $index));
        }
    }

    private function servicePayload(array $item, int $sortOrder): array
    {
        $quantity = max(1, (int) ($item['quantity'] ?? 1));
        $purchase = $this->number($item['unit_purchase_price'] ?? $item['purchase_price'] ?? 0);
        $marginType = ($item['margin_type'] ?? 'amount') === 'percent' ? 'percent' : 'amount';
        $marginValue = $this->number($item['margin_value'] ?? $item['unit_margin'] ?? 0);
        $unitMargin = $marginType === 'percent'
            ? $purchase * ($marginValue / 100)
            : $marginValue;
        $unitSale = $purchase + $unitMargin;

        if (array_key_exists('unit_sale_price', $item) && $item['unit_sale_price'] !== null && $item['unit_sale_price'] !== '') {
            $unitSale = $this->number($item['unit_sale_price']);
        }

        return [
            'service_type' => $item['service_type'] ?? 'other',
            'title' => $item['title'] ?? $this->fallbackTitle($item),
            'description' => $item['description'] ?? '',
            'supplier_name' => $item['supplier_name'] ?? $item['supplier'] ?? null,
            'quantity' => $quantity,
            'unit_purchase_price' => $purchase,
            'margin_type' => $marginType,
            'margin_value' => $marginValue,
            'unit_margin' => $unitMargin,
            'unit_sale_price' => $unitSale,
            'total_purchase' => $purchase * $quantity,
            'total_margin' => $unitMargin * $quantity,
            'total_sale' => $unitSale * $quantity,
            'is_optional' => (bool) ($item['is_optional'] ?? false),
            'data_json' => Arr::where((array) ($item['data_json'] ?? []), fn ($value) => $value !== null && $value !== ''),
            'sort_order' => $sortOrder,
        ];
    }

    private function fallbackTitle(array $item): ?string
    {
        $description = trim((string) ($item['description'] ?? ''));

        return $description !== '' ? mb_substr($description, 0, 80) : null;
    }

    private function resolveNights($customRequest): int
    {
        if ($customRequest->desired_departure_date && $customRequest->desired_return_date) {
            $days = Carbon::parse($customRequest->desired_departure_date)
                ->diffInDays(Carbon::parse($customRequest->desired_return_date), false);

            if ($days > 0) {
                return (int) $days;
            }
        }

        $duration = $this->extractFirstPositiveInteger((string) ($customRequest->desired_duration ?? ''));
        if ($duration > 0) {
            return $duration;
        }

        $text = implode(' ', array_filter([
            $customRequest->requested_services_details ?? null,
            $customRequest->customer_notes ?? null,
            $customRequest->internal_notes ?? null,
        ]));

        return $this->extractFirstPositiveInteger($text);
    }

    private function extractFirstPositiveInteger(string $text): int
    {
        if (preg_match('/(\d+)\s*(?:nuits?|jours?)/iu', $text, $matches) === 1) {
            return max(0, (int) $matches[1]);
        }

        if (preg_match('/^\s*(\d+)\s*$/', $text, $matches) === 1) {
            return max(0, (int) $matches[1]);
        }

        return 0;
    }

    private function number(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '.', (string) $value);
    }
}
