<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Qualquer um pode criar um pedido (guest ou autenticado)
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.ticket_type_id' => ['required', 'integer', 'exists:ticket_types,id'],
            'items.*.category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'items.*.participant_data' => ['required', 'array'],
            'items.*.participant_data.name' => ['required', 'string', 'max:255'],
            'items.*.participant_data.email' => ['required', 'email', 'max:255'],
            'items.*.participant_data.cpf' => [
                'required',
                'string',
                'size:11',
                'cpf', // Usa a regra de validação do pacote geekcom/validator-docs
                function ($attribute, $value, $fail) {
                    // Extrai o índice do item
                    preg_match('/items\.(\d+)\.participant_data\.cpf/', $attribute, $matches);
                    $itemIndex = $matches[1] ?? 0;

                    $eventId = $this->input('event_id');
                    
                    // Verifica CPFs duplicados no mesmo pedido
                    $cpfsInRequest = collect($this->input('items'))
                        ->pluck('participant_data.cpf')
                        ->filter();
                    
                    if ($cpfsInRequest->duplicates()->contains($value)) {
                        $fail('O CPF não pode ser duplicado no mesmo pedido.');
                        return;
                    }

                    // Verifica se o CPF já foi usado em outro pedido PAGO para este evento
                    // Pedidos PENDING não bloqueiam o CPF (usuário pode abandonar e tentar novamente)
                    $exists = \DB::table('order_items')
                        ->join('orders', 'order_items.order_id', '=', 'orders.id')
                        ->where('orders.event_id', $eventId)
                        ->where('orders.status', 'paid')
                        ->whereRaw("JSON_EXTRACT(order_items.participant_data, '$.cpf') = ?", [$value])
                        ->exists();

                    if ($exists) {
                        $fail('Este CPF já está inscrito neste evento.');
                    }
                },
            ],
            'items.*.participant_data.birthdate' => ['required', 'date', 'before:today'],
            'items.*.participant_data.shirt_size' => ['nullable', 'string', 'in:PP,P,M,G,GG,XG'],
            'items.*.participant_data.emergency_contact' => ['nullable', 'string', 'max:255'],
            'items.*.participant_data.rg' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'event_id.required' => 'O evento é obrigatório.',
            'event_id.exists' => 'O evento selecionado não existe.',
            'items.required' => 'É necessário adicionar pelo menos um participante.',
            'items.min' => 'É necessário adicionar pelo menos um participante.',
            'items.*.ticket_type_id.required' => 'O tipo de ingresso é obrigatório.',
            'items.*.ticket_type_id.exists' => 'O tipo de ingresso selecionado não existe.',
            'items.*.category_id.exists' => 'A categoria selecionada não existe.',
            'items.*.participant_data.required' => 'Os dados do participante são obrigatórios.',
            'items.*.participant_data.name.required' => 'O nome é obrigatório.',
            'items.*.participant_data.name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'items.*.participant_data.email.required' => 'O e-mail é obrigatório.',
            'items.*.participant_data.email.email' => 'O e-mail deve ser um endereço válido.',
            'items.*.participant_data.email.max' => 'O e-mail não pode ter mais de 255 caracteres.',
            'items.*.participant_data.cpf.required' => 'O CPF é obrigatório.',
            'items.*.participant_data.cpf.size' => 'O CPF deve ter 11 dígitos.',
            'items.*.participant_data.birthdate.required' => 'A data de nascimento é obrigatória.',
            'items.*.participant_data.birthdate.date' => 'A data de nascimento deve ser uma data válida.',
            'items.*.participant_data.birthdate.before' => 'A data de nascimento deve ser anterior a hoje.',
            'items.*.participant_data.shirt_size.in' => 'O tamanho da camisa deve ser PP, P, M, G, GG ou XG.',
            'items.*.participant_data.emergency_contact.max' => 'O contato de emergência não pode ter mais de 255 caracteres.',
            'items.*.participant_data.rg.max' => 'O RG não pode ter mais de 20 caracteres.',
        ];
    }

    /**
     * Prepara os dados para validação
     */
    protected function prepareForValidation(): void
    {
        // Remove formatação do CPF se vier com pontos e traços
        if ($this->has('items')) {
            $items = collect($this->items)->map(function ($item) {
                if (isset($item['participant_data']['cpf'])) {
                    $item['participant_data']['cpf'] = preg_replace('/[^0-9]/', '', $item['participant_data']['cpf']);
                }
                return $item;
            })->toArray();

            $this->merge(['items' => $items]);
        }
    }

    /**
     * Validação adicional após a validação básica
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Valida que todos os ticket_types pertencem ao evento
            $eventId = $this->input('event_id');
            $ticketTypeIds = collect($this->input('items'))->pluck('ticket_type_id')->unique();

            foreach ($ticketTypeIds as $ticketTypeId) {
                $ticketType = \App\Models\TicketType::find($ticketTypeId);
                if (!$ticketType) {
                    continue;
                }

                if ($ticketType->event_id != $eventId) {
                    $validator->errors()->add(
                        'items',
                        "O tipo de ingresso ID {$ticketTypeId} não pertence ao evento selecionado."
                    );
                    continue;
                }

                if (!$ticketType->isAvailableForPurchase()) {
                    $validator->errors()->add(
                        'items',
                        "O tipo de ingresso \"{$ticketType->name}\" não está disponível para compra."
                    );
                }
            }

            // Valida que todas as categorias pertencem ao evento e estão ativas
            $categoryIds = collect($this->input('items'))
                ->pluck('category_id')
                ->filter()
                ->unique();

            foreach ($categoryIds as $categoryId) {
                $category = \App\Models\Category::find($categoryId);
                if (!$category) {
                    continue;
                }

                if ($category->event_id != $eventId) {
                    $validator->errors()->add(
                        'items',
                        "A categoria ID {$categoryId} não pertence ao evento selecionado."
                    );
                    continue;
                }

                if (!$category->active) {
                    $validator->errors()->add(
                        'items',
                        "A categoria \"{$category->name}\" não está disponível."
                    );
                }
            }
        });
    }
}
