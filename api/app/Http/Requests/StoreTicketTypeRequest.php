<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isSuperAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $eventId = $this->route('event')->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('ticket_types')->where('event_id', $eventId),
            ],
            'description' => ['nullable', 'string'],
            'price_cents' => ['required', 'integer', 'min:0'],
            'currency' => ['string', 'max:10'],
            'quota' => ['nullable', 'integer', 'min:1'],
            'start_sale' => ['nullable', 'date'],
            'end_sale' => ['nullable', 'date', 'after_or_equal:start_sale'],
            'attributes' => ['nullable', 'array'],
            'active' => ['boolean'],
            'allows_shirt_size' => ['boolean'],
            'requires_senior_age' => ['boolean'],
            'senior_min_age' => ['nullable', 'integer', 'min:1', 'max:120', 'required_if:requires_senior_age,true'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', Rule::exists('categories', 'id')->where('event_id', $eventId)],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome do tipo de ingresso é obrigatório.',
            'name.unique' => 'Já existe um tipo de ingresso com este nome para este evento.',
            'price_cents.required' => 'O preço é obrigatório.',
            'price_cents.integer' => 'O preço deve ser um número inteiro em centavos.',
            'price_cents.min' => 'O preço deve ser maior ou igual a 0.',
            'quota.integer' => 'A cota deve ser um número inteiro.',
            'quota.min' => 'A cota deve ser no mínimo 1.',
            'start_sale.date' => 'A data de início das vendas deve ser uma data válida.',
            'end_sale.date' => 'A data de fim das vendas deve ser uma data válida.',
            'end_sale.after_or_equal' => 'A data de fim das vendas deve ser posterior ou igual à data de início.',
            'attributes.array' => 'Os atributos devem ser um objeto JSON.',
            'senior_min_age.required_if' => 'A idade mínima é obrigatória para um ingresso de idoso.',
            'senior_min_age.integer' => 'A idade mínima deve ser um número inteiro.',
            'senior_min_age.min' => 'A idade mínima deve ser no mínimo 1 ano.',
            'senior_min_age.max' => 'A idade mínima deve ser no máximo 120 anos.',
            'category_ids.array' => 'As categorias devem ser uma lista.',
            'category_ids.*.exists' => 'Uma das categorias selecionadas não pertence a este evento.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('currency')) {
            $this->merge(['currency' => 'BRL']);
        }
    }
}
