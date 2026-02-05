<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')->where('event_id', $eventId),
            ],
            'gender' => ['nullable', 'in:M,F,X'],
            'min_age' => ['nullable', 'integer', 'min:0'],
            'max_age' => ['nullable', 'integer', 'min:0', 'gte:min_age'],
            'active' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome da categoria é obrigatório.',
            'name.unique' => 'Já existe uma categoria com este nome para este evento.',
            'gender.in' => 'O gênero deve ser M (masculino), F (feminino) ou X (misto).',
            'min_age.integer' => 'A idade mínima deve ser um número inteiro.',
            'min_age.min' => 'A idade mínima deve ser maior ou igual a 0.',
            'max_age.integer' => 'A idade máxima deve ser um número inteiro.',
            'max_age.min' => 'A idade máxima deve ser maior ou igual a 0.',
            'max_age.gte' => 'A idade máxima deve ser maior ou igual à idade mínima.',
        ];
    }
}
