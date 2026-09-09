<?php

namespace App\Http\Requests\Admin\Finance;

use App\Models\DepartureCharge;
use App\Support\FinanceControlPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Charge liee a un projet de voyage (departure_charges).
 *
 * Double controle : le Gate d'acces au module ET la permission fonctionnelle.
 */
class TravelExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return FinanceControlPermissions::userIsFinanceAdmin($user)
            && (bool) $user?->can(FinanceControlPermissions::EXPENSES_MANAGE);
    }

    public function rules(): array
    {
        return [
            'departure_id' => ['required', 'integer', 'exists:departures,id'],
            'charge_type_id' => ['nullable', 'integer', 'exists:charge_types,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:finance_suppliers,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'title' => ['required', 'string', 'max:190'],
            'supplier_name' => ['nullable', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
            // Montants strictement positifs ou nuls : aucune charge negative n'est admise.
            'planned_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'amount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'paid_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99', 'lte:amount'],
            'currency' => ['nullable', 'string', 'max:8'],
            'status' => ['required', Rule::in(array_keys(DepartureCharge::STATUS_LABELS))],
            'payment_method' => ['required', Rule::in(DepartureCharge::PAYMENT_METHODS)],
            'charge_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'paid_at' => ['nullable', 'date'],
            'invoice_reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'paid_amount.lte' => 'Le montant paye ne peut pas depasser le montant reel de la charge.',
        ];
    }

    public function attributes(): array
    {
        return [
            'planned_amount' => 'montant prevu',
            'amount' => 'montant reel',
            'paid_amount' => 'montant paye',
        ];
    }
}
