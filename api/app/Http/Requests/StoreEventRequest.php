<?php

namespace App\Http\Requests;

use App\Enums\PayoutMode;
use App\Models\Organizer;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Apenas super admin pode criar eventos
        return $this->user() && $this->user()->isSuperAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'organizer_id' => 'required|exists:organizers,id',
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:events,slug',
            'description' => 'nullable|string',
            'state' => 'required|string|size:2',
            'city' => 'required|string|max:100',
            'venue' => 'required|string|max:255',
            'date_start' => 'required|date|after:now',
            'date_end' => 'required|date|after_or_equal:date_start',
            'max_participants' => 'nullable|integer|min:1',
            'banner' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'banner_url' => 'nullable|url',
            'results_url' => 'nullable|url|max:2048',
            // Modalidade de recebimento e taxa da plataforma (fração 0–1).
            'payout_mode' => ['nullable', Rule::in(PayoutMode::values())],
            'platform_fee_rate' => 'nullable|numeric|min:0|max:1',
            'meta' => 'nullable|array',
        ];
    }

    /**
     * Split só é permitido quando o organizador tem uma conta MP conectada.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->input('payout_mode') !== PayoutMode::SPLIT->value) {
                    return;
                }

                $organizer = Organizer::find($this->input('organizer_id'));

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
            'date_start.after' => 'A data de início deve ser futura.',
            'date_end.after_or_equal' => 'A data de término deve ser igual ou posterior à data de início.',
        ];
    }
}
