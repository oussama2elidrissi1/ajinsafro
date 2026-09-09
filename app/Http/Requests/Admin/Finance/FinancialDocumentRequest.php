<?php

namespace App\Http\Requests\Admin\Finance;

use App\Models\FinancialDocument;
use App\Support\FinanceControlPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinancialDocumentRequest extends FormRequest
{
    /** Types de mouvements auxquels un justificatif peut etre rattache. */
    public const ALLOWED_MOVEMENTS = [
        'payment' => \App\Models\ReservationPayment::class,
        'travel_expense' => \App\Models\DepartureCharge::class,
        'structural_expense' => \App\Models\StructuralExpense::class,
    ];

    public function authorize(): bool
    {
        $user = $this->user();

        return FinanceControlPermissions::userIsFinanceAdmin($user)
            && (bool) $user?->can(FinanceControlPermissions::DOCUMENTS_MANAGE);
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', Rule::in(array_keys(FinancialDocument::TYPE_LABELS))],
            'status' => ['required', Rule::in(array_keys(FinancialDocument::STATUS_LABELS))],
            'reference' => ['nullable', 'string', 'max:120'],
            'document_date' => ['nullable', 'date'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'currency' => ['nullable', 'string', 'max:8'],
            // Rattachement au mouvement : cle logique + identifiant, jamais une classe libre.
            'movement_type' => ['nullable', Rule::in(array_keys(self::ALLOWED_MOVEMENTS))],
            'movement_id' => ['nullable', 'required_with:movement_type', 'integer', 'min:1'],
            'departure_id' => ['nullable', 'integer', 'exists:departures,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:finance_suppliers,id'],
            'client_name' => ['nullable', 'string', 'max:190'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'movement_id.required_with' => 'Precisez le mouvement financier a rattacher.',
        ];
    }
}
