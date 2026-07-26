<?php

namespace App\Http\Requests;

use App\Enums\EventStatus;
use App\Enums\PayoutMode;
use App\Models\Organizer;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Apenas super admin pode editar eventos
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
            'organizer_id' => 'sometimes|required|exists:organizers,id',
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:events,slug,'.$eventId,
            'description' => 'nullable|string',
            'state' => 'sometimes|required|string|size:2',
            'city' => 'sometimes|required|string|max:100',
            'venue' => 'sometimes|required|string|max:255',
            'date_start' => 'sometimes|required|date',
            'date_end' => 'sometimes|required|date|after_or_equal:date_start',
            'max_participants' => 'nullable|integer|min:1',
            'banner' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'banner_url' => 'nullable|url',
            'results_url' => 'nullable|url',
            'status' => ['sometimes', Rule::in(EventStatus::values())],
            'payout_mode' => ['sometimes', Rule::in(PayoutMode::values())],
            'platform_fee_rate' => 'sometimes|nullable|numeric|min:0|max:1',
            'meta' => 'nullable|array',
        ];
    }

    /**
     * Split só é permitido quando o organizador tem uma conta MP conectada.
     * Usa o organizador informado na requisição ou, na ausência, o do evento.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->input('payout_mode') !== PayoutMode::SPLIT->value) {
                    return;
                }

                $organizerId = $this->input('organizer_id') ?? $this->route('event')->organizer_id;
                $organizer = Organizer::find($organizerId);

                if (! $organizer || ! $organizer->hasConnectedPaymentAccount()) {
                    $validator->errors()->add(
                        'payout_mode',
                        'O organizador precisa conectar a conta do Mercado Pago antes de habilitar o split.'
                    );
                }
            },
        ];
    }

    /**
     * Mensagens de erro personalizadas
     */
    public function messages(): array
    {
        return [
            'slug.unique' => 'Este slug já está em uso.',
            'date_end.after_or_equal' => 'A data de término deve ser igual ou posterior à data de início.',
        ];
    }
}
