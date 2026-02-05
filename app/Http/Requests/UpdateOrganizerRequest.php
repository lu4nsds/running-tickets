<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Apenas super admin pode editar organizadores
        return $this->user() && $this->user()->isSuperAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $organizerId = $this->route('organizer')->id;
        
        return [
            'name' => 'sometimes|required|string|max:255',
            'document' => 'sometimes|required|string|max:20|unique:organizers,document,' . $organizerId,
            'email' => 'sometimes|required|email|max:255|unique:organizers,email,' . $organizerId,
            'phone' => 'nullable|string|max:20',
            'status' => 'sometimes|in:active,inactive',
        ];
    }

    /**
     * Mensagens de erro personalizadas
     */
    public function messages(): array
    {
        return [
            'document.unique' => 'Este documento já está cadastrado.',
            'email.unique' => 'Este e-mail já está cadastrado.',
        ];
    }
}
