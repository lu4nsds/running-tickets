<?php

namespace App\Http\Requests;

use App\Enums\OrganizerRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizerUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        $userExists = User::where('email', $this->email)->exists();

        return [
            'email' => ['required', 'email', 'max:255'],
            'role'  => ['required', Rule::in(OrganizerRole::values())],
            'name'  => [Rule::requiredIf(!$userExists), 'nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'O e-mail é obrigatório.',
            'email.email'    => 'O e-mail deve ser um endereço válido.',
            'role.required'  => 'O cargo é obrigatório.',
            'role.in'        => 'O cargo deve ser admin ou staff.',
            'name.required'  => 'O nome é obrigatório para novos usuários.',
            'name.max'       => 'O nome não pode ter mais de 255 caracteres.',
        ];
    }
}
