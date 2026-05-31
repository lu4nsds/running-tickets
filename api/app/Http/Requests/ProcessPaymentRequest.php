<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // OptionalAuth middleware no route — pagamento aberto a guest também.
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'in:credit_card,debit_card,pix'],
            'token' => ['required_if:payment_method,credit_card,debit_card', 'nullable', 'string'],
            'payment_method_id' => ['required_if:payment_method,credit_card,debit_card', 'nullable', 'string'],
            'installments' => ['nullable', 'integer', 'min:1', 'max:12'],
            'payer' => ['required', 'array'],
            'payer.email' => ['required', 'email', 'regex:/^[\x00-\x7F]+$/'],
            'payer.phone' => ['required', 'string', 'max:20'],
            'payer.identification' => ['required', 'array'],
            'payer.identification.type' => ['required', 'string'],
            'payer.identification.number' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'payer.email.regex' => 'O e-mail deve conter apenas caracteres ASCII (sem acentos).',
            'payment_method.in' => 'Método de pagamento inválido.',
            'token.required_if' => 'Token do cartão é obrigatório para pagamentos com cartão.',
        ];
    }
}
