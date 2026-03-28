<?php

namespace App\Http\Requests;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePartnerRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'raison_sociale' => ['required', 'string', 'max:190'],
            'nom_commercial' => ['nullable', 'string', 'max:190'],
            'nom_responsable' => ['required', 'string', 'max:190'],
            'email' => [
                'required',
                'email',
                'max:190',
                Rule::unique(User::class, 'email'),
            ],
            'telephone' => ['required', 'string', 'max:50', Rule::unique(Partner::class, 'telephone')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'adresse' => ['nullable', 'string', 'max:500'],
            'ville' => ['nullable', 'string', 'max:100'],
            'code_postal' => ['nullable', 'string', 'max:20'],
            'pays' => ['nullable', 'string', 'max:100'],
            'ice' => ['nullable', 'string', 'max:50'],
            'if' => ['nullable', 'string', 'max:50'],
            'rc' => ['nullable', 'string', 'max:50'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ];
    }
}
