<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBatchOrderCancellationRequest extends FormRequest
{
    /**
     * A autorização de propriedade/elegibilidade é feita no controller via
     * Policy ($this->authorize('create', ...)) para cada pedido informado.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'references' => ['required', 'array', 'min:1'],
            'references.*' => ['required', 'string', 'exists:orders,reference'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'references.required' => 'Selecione ao menos um pedido para solicitar o cancelamento.',
            'references.min' => 'Selecione ao menos um pedido para solicitar o cancelamento.',
            'references.*.exists' => 'Um dos pedidos informados não foi encontrado.',
            'reason.required' => 'O motivo do cancelamento é obrigatório.',
            'reason.max' => 'O motivo não pode ter mais de 1000 caracteres.',
        ];
    }
}
