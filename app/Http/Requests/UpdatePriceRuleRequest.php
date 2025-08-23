<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePriceRuleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('price_rules', 'name')->ignore($this->price_rule->id)
            ],
            'value_pct' => 'sometimes|required|integer|min:0|max:100'
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'start_date.required' => 'La fecha de inicio es obligatoria',
            'start_date.date' => 'La fecha de inicio debe ser una fecha válida',
            'end_date.required' => 'La fecha de fin es obligatoria',
            'end_date.date' => 'La fecha de fin debe ser una fecha válida',
            'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio',
            'name.required' => 'El nombre es obligatorio',
            'name.string' => 'El nombre debe ser texto',
            'name.max' => 'El nombre no puede tener más de 100 caracteres',
            'name.unique' => 'Ya existe una regla de precio con este nombre',
            'value_pct.required' => 'El porcentaje es obligatorio',
            'value_pct.integer' => 'El porcentaje debe ser un número entero',
            'value_pct.min' => 'El porcentaje debe ser mayor o igual a 0',
            'value_pct.max' => 'El porcentaje no puede ser mayor a 100'
        ];
    }
}
