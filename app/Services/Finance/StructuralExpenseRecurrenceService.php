<?php

namespace App\Services\Finance;

use App\Models\StructuralExpense;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Generation des charges de structure recurrentes.
 *
 * Regle anti-doublon : chaque occurrence est identifiee par (recurrence_parent_id,
 * recurrence_period) ou recurrence_period est la periode AAAA-MM d'echeance. Un index
 * unique en base fait foi, et la generation est idempotente : relancer le mois suivant
 * ne recree jamais une occurrence deja presente.
 *
 * La generation n'est jamais automatique : elle est proposee a l'administrateur, qui
 * previsualise les occurrences avant de les confirmer.
 */
class StructuralExpenseRecurrenceService
{
    /**
     * Modeles recurrents actifs (les occurrences generees ne sont pas elles-memes recurrentes).
     *
     * @return Collection<int, StructuralExpense>
     */
    public function templates(): Collection
    {
        return StructuralExpense::query()
            ->accountable()
            ->where('is_recurring', true)
            ->whereNotNull('recurrence')
            ->whereNull('recurrence_parent_id')
            ->with('branch:id,name')
            ->orderBy('label')
            ->get();
    }

    /**
     * Occurrences qui manquent jusqu'a une date donnee, sans rien ecrire.
     *
     * @return Collection<int, array{template: StructuralExpense, period: string, date: string, amount: float}>
     */
    public function pendingOccurrences(?CarbonImmutable $until = null): Collection
    {
        $until = $until ?: CarbonImmutable::now()->endOfMonth();
        $templates = $this->templates();

        if ($templates->isEmpty()) {
            return collect();
        }

        $existing = StructuralExpense::query()
            ->withTrashed()
            ->whereIn('recurrence_parent_id', $templates->pluck('id'))
            ->get(['recurrence_parent_id', 'recurrence_period'])
            ->groupBy('recurrence_parent_id')
            ->map(fn (Collection $rows) => $rows->pluck('recurrence_period')->filter()->all());

        $pending = collect();

        foreach ($templates as $template) {
            $done = $existing->get($template->id, []);

            foreach ($this->expectedDates($template, $until) as $date) {
                $period = $date->format('Y-m');

                if (in_array($period, $done, true)) {
                    continue;
                }

                $pending->push([
                    'template' => $template,
                    'period' => $period,
                    'date' => $date->toDateString(),
                    'amount' => round((float) $template->amount, 2),
                ]);
            }
        }

        return $pending->sortBy('date')->values();
    }

    /**
     * Cree les occurrences manquantes. Retourne le nombre reellement insere.
     *
     * L'insertion est protegee par l'index unique : une execution concurrente ne peut pas
     * produire de doublon, elle echoue simplement sur l'occurrence deja creee.
     */
    public function generate(?CarbonImmutable $until, int $userId): int
    {
        $pending = $this->pendingOccurrences($until);

        if ($pending->isEmpty()) {
            return 0;
        }

        $created = 0;

        DB::transaction(function () use ($pending, $userId, &$created): void {
            foreach ($pending as $occurrence) {
                /** @var StructuralExpense $template */
                $template = $occurrence['template'];

                $alreadyThere = StructuralExpense::query()
                    ->withTrashed()
                    ->where('recurrence_parent_id', $template->id)
                    ->where('recurrence_period', $occurrence['period'])
                    ->exists();

                if ($alreadyThere) {
                    continue;
                }

                StructuralExpense::query()->create([
                    'branch_id' => $template->branch_id,
                    'supplier_id' => $template->supplier_id,
                    'category' => $template->category,
                    'label' => $template->label,
                    'description' => $template->description,
                    'amount' => $template->amount,
                    'paid_amount' => 0,
                    'currency' => $template->currency,
                    'status' => StructuralExpense::STATUS_PLANNED,
                    'expense_date' => $occurrence['date'],
                    'due_date' => $occurrence['date'],
                    'payment_method' => $template->payment_method,
                    // Une occurrence n'est jamais elle-meme un modele : pas de recurrence en cascade.
                    'is_recurring' => false,
                    'recurrence' => null,
                    'recurrence_parent_id' => $template->id,
                    'recurrence_period' => $occurrence['period'],
                    'notes' => $template->notes,
                    'created_by' => $userId,
                ]);

                $created++;
            }
        });

        return $created;
    }

    /**
     * Dates d'echeance attendues pour un modele, de sa date d'origine jusqu'a `until`.
     *
     * @return list<CarbonImmutable>
     */
    private function expectedDates(StructuralExpense $template, CarbonImmutable $until): array
    {
        $step = StructuralExpense::RECURRENCE_MONTHS[$template->recurrence] ?? 0;

        if ($step <= 0) {
            return [];
        }

        $limit = $until;
        if ($template->recurrence_until) {
            $end = CarbonImmutable::parse($template->recurrence_until);
            $limit = $end->lessThan($limit) ? $end : $limit;
        }

        $dates = [];
        $cursor = CarbonImmutable::parse($template->expense_date)->addMonths($step);
        $guard = 0;

        while ($cursor->lessThanOrEqualTo($limit) && $guard < 240) {
            $dates[] = $cursor;
            $cursor = $cursor->addMonths($step);
            $guard++;
        }

        return $dates;
    }
}
