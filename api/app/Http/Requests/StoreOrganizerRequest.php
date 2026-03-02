<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Apenas super admin pode criar organizadores
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
            'name' => 'required|string|max:255',
            'document' => 'required|string|max:20|unique:organizers,document',
            'email' => 'required|email|max:255|unique:organizers,email',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'address_complement' => 'nullable|string|max:100',
            'neighborhood' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'state' => 'required|string|size:2',
            'zip_code' => 'required|string|max:10',
            'status' => 'nullable|in:active,inactive,blocked',
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
