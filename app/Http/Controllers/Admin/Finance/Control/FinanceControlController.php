<?php

namespace App\Http\Controllers\Admin\Finance\Control;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Voyage;
use App\Support\FinanceControlPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Socle commun des ecrans « Finance & Controle ».
 *
 * Le groupe de routes porte deja le middleware `finance.control`. Cette classe ajoute la
 * seconde barriere demandee : chaque action verifie explicitement le Gate d'acces au module
 * ET la permission fonctionnelle de l'ecran. Aucune protection n'est purement front.
 */
abstract class FinanceControlController extends Controller
{
    /**
     * Refuse l'acces si l'utilisateur n'est pas administrateur ou n'a pas la permission.
     */
    protected function authorizeFinance(Request $request, string $permission): void
    {
        $user = $request->user();

        abort_unless(FinanceControlPermissions::userIsFinanceAdmin($user), 403, 'Module Finance & Controle reserve a l\'administration.');
        abort_unless((bool) $user?->can($permission), 403, 'Permission insuffisante pour cette section financiere.');
    }

    /**
     * Filtres partages par les ecrans du module.
     *
     * @return array<string, mixed>
     */
    protected function commonFilters(Request $request): array
    {
        return [
            'date_from' => $this->date($request, 'date_from'),
            'date_to' => $this->date($request, 'date_to'),
            'voyage_id' => $this->id($request, 'voyage_id'),
            'departure_id' => $this->id($request, 'departure_id'),
            'branch_id' => $this->id($request, 'branch_id'),
            'status' => $this->text($request, 'status'),
            'profitability' => in_array($request->query('profitability'), ['rentable', 'deficitaire'], true)
                ? (string) $request->query('profitability')
                : null,
            'search' => $this->text($request, 'search'),
        ];
    }

    protected function date(Request $request, string $key): ?string
    {
        $value = trim((string) $request->query($key, ''));

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : null;
    }

    protected function id(Request $request, string $key): ?int
    {
        $value = (int) $request->query($key, 0);

        return $value > 0 ? $value : null;
    }

    protected function text(Request $request, string $key): ?string
    {
        $value = trim((string) $request->query($key, ''));

        return $value !== '' ? mb_substr($value, 0, 190) : null;
    }

    /** @return Collection<int, Branch> */
    protected function branchOptions(): Collection
    {
        return Branch::query()->orderBy('name')->get(['id', 'name']);
    }

    /** @return Collection<int, Voyage> */
    protected function voyageOptions(): Collection
    {
        return Voyage::query()->orderBy('name')->get(['id', 'name']);
    }
}
