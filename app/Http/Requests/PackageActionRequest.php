<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PackageActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public API
    }

    public function rules(): array
    {
        return [
            'action' => 'required|string|in:add,remove,modify',
            'item_id' => 'required_if:action,remove,modify|integer|exists:travel_day_items,id',
            'new_option' => 'required_if:action,modify|array',
            'new_option.title' => 'required_with:new_option|string',
            'new_option.price_delta' => 'required_with:new_option|integer',
            'add_data' => 'required_if:action,add|array',
            'add_data.day_number' => 'required_with:add_data|integer|min:1',
            'add_data.type' => 'required_with:add_data|string|in:flight,hotel_stay,transfer,activity,meal,addon',
            'add_data.title' => 'required_with:add_data|string',
            'add_data.price_delta_per_person' => 'required_with:add_data|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'L\'action est requise.',
            'action.in' => 'L\'action doit être add, remove ou modify.',
            'item_id.required_if' => 'L\'ID de l\'item est requis pour cette action.',
            'item_id.exists' => 'L\'item spécifié n\'existe pas.',
        ];
    }
}
