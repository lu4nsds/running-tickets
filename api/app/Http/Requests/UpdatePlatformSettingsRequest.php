<?php

namespace App\Http\Requests;

use App\Enums\PlatformSettingKey;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePlatformSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Apenas super admin configura a plataforma
        return $this->user() && $this->user()->isSuperAdmin();
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            PlatformSettingKey::FEEDBACK_FORM_URL->value => ['nullable', 'url', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'feedback_form_url.url' => 'Informe uma URL válida para o formulário de feedback.',
        ];
    }

    /**
     * Campo limpo no admin chega como string vazia; normaliza para null para
     * que `getValue()` volte a devolver o default.
     */
    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (PlatformSettingKey::values() as $key) {
            if ($this->has($key) && trim((string) $this->input($key)) === '') {
                $normalized[$key] = null;
            }
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }
}
