<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketTypeRequest extends FormRequest
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
        $ticketTypeId = $this->route('ticket_type')->id;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('ticket_types')
                    ->where('event_id', $eventId)
                    ->ignore($ticketTypeId),
            ],
            'description' => ['nullable', 'string'],
            'price_cents' => ['sometimes', 'required', 'integer', 'min:0'],
            'currency' => ['string', 'max:10'],
            'quota' => ['nullable', 'integer', 'min:1'],
            'start_sale' => ['nullable', 'date'],
            'end_sale' => ['nullable', 'date', 'after_or_equal:start_sale'],
            'attributes' => ['nullable', 'array'],
            'active' => ['boolean'],
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
        ];
    }
}
