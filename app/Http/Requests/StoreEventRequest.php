<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'city' => 'required|string|max:100',
            'venue' => 'required|string|max:255',
            'date_start' => 'required|date|after:now',
            'date_end' => 'required|date|after_or_equal:date_start',
            'max_participants' => 'nullable|integer|min:1',
            'banner' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'banner_url' => 'nullable|url',
            'meta' => 'nullable|array',
            'payout_mode' => 'nullable|in:direct,platform',
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
