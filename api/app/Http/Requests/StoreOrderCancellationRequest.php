<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderCancellationRequest extends FormRequest
{
    /**
     * A autorização de propriedade/elegibilidade é feita no controller via
     * Policy ($this->authorize('create', ...)), pois depende do pedido.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'O motivo do cancelamento é obrigatório.',
            'reason.max' => 'O motivo não pode ter mais de 1000 caracteres.',
        ];
    }
}
