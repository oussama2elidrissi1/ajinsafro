@extends('layouts.finance-control')

@section('title', 'Charges recurrentes')

@php
    // Formatage monetaire unique pour tout le module.
    $money = fn ($value) => number_format((float) $value, 2, ',', ' ').' DH';
@endphp

@section('finance_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <div>
            <h4 class="mb-1">Charges recurrentes</h4>
            <p class="text-muted mb-0 small">Previsualisez les occurrences manquantes avant de les generer. La generation est sans doublon possible.</p>
        </div>
        <a href="{{ route('admin.finance.control.structural-expenses.index') }}" class="btn btn-sm btn-outline-secondary">Retour aux charges</a>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white border-0 pt-3">
            <h6 class="mb-0">Modeles recurrents actifs</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-3">Libelle</th>
                        <th>Agence</th>
                        <th>Categorie</th>
                        <th class="text-end">Montant</th>
                        <th>Periodicite</th>
                        <th>Depuis</th>
                        <th>Jusqu'au</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($templates as $template)
                        <tr>
                            <td class="px-3">{{ $template->label }}</td>
                            <td>{{ $template->branch?->name ?: 'Toutes' }}</td>
                            <td>{{ $template->category_label }}</td>
                            <td class="text-end">{{ $money($template->amount) }}</td>
                            <td>{{ \App\Models\StructuralExpense::RECURRENCE_LABELS[$template->recurrence] ?? '-' }}</td>
                            <td class="text-nowrap">{{ $template->expense_date?->format('d/m/Y') }}</td>
                            <td class="text-nowrap">{{ $template->recurrence_until?->format('d/m/Y') ?: 'Sans fin' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Aucune charge recurrente definie.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="mb-0">Occurrences a generer ({{ $pending->count() }})</h6>
            <form method="POST" action="{{ route('admin.finance.control.structural-expenses.recurrences.generate') }}" class="d-flex gap-2 align-items-end"
                  onsubmit="return confirm('Generer les occurrences listees ?');">
                @csrf
                <div>
                    <label class="form-label small text-muted mb-0">Jusqu'au</label>
                    <input type="date" name="until" class="form-control form-control-sm" value="{{ $until }}">
                </div>
                <button class="btn btn-sm btn-primary" @disabled($pending->isEmpty())>Generer</button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-3">Periode</th>
                        <th>Date prevue</th>
                        <th>Libelle</th>
                        <th>Agence</th>
                        <th class="text-end">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pending as $occurrence)
                        <tr>
                            <td class="px-3">{{ $occurrence['period'] }}</td>
                            <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($occurrence['date'])->format('d/m/Y') }}</td>
                            <td>{{ $occurrence['template']->label }}</td>
                            <td>{{ $occurrence['template']->branch?->name ?: 'Toutes' }}</td>
                            <td class="text-end">{{ $money($occurrence['amount']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Tout est a jour : aucune occurrence manquante.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
