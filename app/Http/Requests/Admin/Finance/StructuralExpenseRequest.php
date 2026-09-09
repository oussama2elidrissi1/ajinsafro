<?php

namespace App\Http\Requests\Admin\Finance;

use App\Models\StructuralExpense;
use App\Support\FinanceControlPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StructuralExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return FinanceControlPermissions::userIsFinanceAdmin($user)
            && (bool) $user?->can(FinanceControlPermissions::STRUCTURAL_EXPENSES_MANAGE);
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:finance_suppliers,id'],
            'category' => ['required', Rule::in(array_keys(StructuralExpense::CATEGORY_LABELS))],
            'label' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
            'amount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'paid_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99', 'lte:amount'],
            'currency' => ['nullable', 'string', 'max:8'],
            'status' => ['required', Rule::in(array_keys(StructuralExpense::STATUS_LABELS))],
            'expense_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'paid_at' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'max:40'],
            'is_recurring' => ['nullable', 'boolean'],
            'recurrence' => ['nullable', 'required_if:is_recurring,1', Rule::in(array_keys(StructuralExpense::RECURRENCE_LABELS))],
            'recurrence_until' => ['nullable', 'date', 'after_or_equal:expense_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'paid_amount.lte' => 'Le montant paye ne peut pas depasser le montant de la charge.',
            'recurrence.required_if' => 'Choisissez une periodicite pour une charge recurrente.',
        ];
    }
}
