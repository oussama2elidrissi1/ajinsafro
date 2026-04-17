<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Departure;
use App\Models\Reservation;
use App\Models\Voyage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DepartureController extends Controller
{
    public function store(Request $request, Voyage $voyage): RedirectResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => ['required', 'string', Rule::in(Departure::STATUSES)],
            'total_capacity' => 'required|integer|min:0',
            'base_price' => 'nullable|numeric|min:0',
        ]);

        $already = Departure::query()
            ->where('voyage_id', $voyage->id)
            ->whereDate('start_date', $validated['start_date'])
            ->exists();

        $reserved = 0;
        [$status, $available] = $this->normalizeStatusAndAvailability(
            $validated['status'],
            (int) $validated['total_capacity'],
            $reserved
        );

        Departure::updateOrCreate(
            [
                'voyage_id' => $voyage->id,
                'start_date' => $validated['start_date'],
            ],
            [
                'end_date' => $validated['end_date'] ?? null,
                'status' => $status,
                'total_capacity' => (int) $validated['total_capacity'],
                'reserved_capacity' => $reserved,
                'available_capacity' => $available,
                'base_price' => $validated['base_price'] ?? null,
            ]
        );

        return $this->resolveRedirect($request, $voyage)
            ->with('success', $already ? 'Départ mis à jour (date déjà existante pour ce voyage).' : 'Départ ajouté.');
    }

    public function update(Request $request, Voyage $voyage, Departure $departure): RedirectResponse
    {
        if ($departure->voyage_id !== $voyage->id) {
            abort(404);
        }
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => ['required', 'string', Rule::in(Departure::STATUSES)],
            'total_capacity' => 'required|integer|min:0',
            'base_price' => 'nullable|numeric|min:0',
        ]);

        $other = Departure::query()
            ->where('voyage_id', $voyage->id)
            ->where('id', '!=', $departure->id)
            ->whereDate('start_date', $validated['start_date'])
            ->first();

        if ($other) {
            return $this->resolveRedirect($request, $voyage)
                ->withErrors(['start_date' => 'Une autre ligne existe déjà pour cette date de départ. Modifiez-la ou fusionnez les départs.']);
        }

        $reserved = $this->computeReservedCapacity($departure);
        [$status, $available] = $this->normalizeStatusAndAvailability(
            $validated['status'],
            (int) $validated['total_capacity'],
            $reserved
        );

        $departure->update([
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'status' => $status,
            'total_capacity' => (int) $validated['total_capacity'],
            'reserved_capacity' => $reserved,
            'available_capacity' => $available,
            'base_price' => $validated['base_price'] ?? null,
        ]);

        return $this->resolveRedirect($request, $voyage)
            ->with('success', 'Départ mis à jour.');
    }

    public function destroy(Voyage $voyage, Departure $departure): RedirectResponse
    {
        if ($departure->voyage_id !== $voyage->id) {
            abort(404);
        }
        $departure->delete();
        return $this->resolveRedirect($request, $voyage)
            ->with('success', 'Départ supprimé.');
    }

    private function stockConsumingStatuses(): array
    {
        $consuming = (array) config('booking_lifecycle.stock_consuming_statuses', []);
        if ((bool) config('booking_lifecycle.option_holds_stock', false)) {
            $consuming = array_merge($consuming, (array) config('booking_lifecycle.stock_hold_statuses', []));
        }

        return array_values(array_unique(array_filter($consuming, fn ($s) => is_string($s) && $s !== '')));
    }

    private function computeReservedCapacity(Departure $departure): int
    {
        return (int) Reservation::query()
            ->where('departure_id', $departure->id)
            ->whereIn('status', $this->stockConsumingStatuses())
            ->sum('passengers_count');
    }

    private function normalizeStatusAndAvailability(string $status, int $totalCapacity, int $reservedCapacity): array
    {
        $total = max(0, $totalCapacity);
        $reserved = max(0, $reservedCapacity);

        if ($reserved > $total) {
            throw ValidationException::withMessages([
                'total_capacity' => 'Capacité totale inférieure au nombre de places déjà réservées.',
            ]);
        }

        $available = max(0, $total - $reserved);

        if ($available === 0 && in_array($status, [Departure::STATUS_OPEN, Departure::STATUS_LIMITED, Departure::STATUS_FULL], true)) {
            $status = Departure::STATUS_FULL;
        }

        if ($available > 0 && $status === Departure::STATUS_FULL) {
            $status = Departure::STATUS_OPEN;
        }

        return [$status, $available];
    }

    private function resolveRedirect(Request $request, Voyage $voyage): RedirectResponse
    {
        $target = trim((string) $request->input('redirect_to', ''));

        if ($target !== '') {
            $path = (string) (parse_url($target, PHP_URL_PATH) ?? '');
            if (str_starts_with($path, '/admin/')) {
                return redirect()->to($target);
            }
        }

        return redirect()->route('admin.circuits.voyages.edit', $voyage->wp_post_id ?? $voyage->id);
    }
}
