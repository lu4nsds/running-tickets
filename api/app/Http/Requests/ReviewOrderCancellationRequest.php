<?php

namespace App\Http\Requests;

use App\Models\OrderCancellation;
use Illuminate\Foundation\Http\FormRequest;

class ReviewOrderCancellationRequest extends FormRequest
{
    /**
     * Somente super admin pode avaliar (Policy::review).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('review', OrderCancellation::class) ?? false;
    }

    public function rules(): array
    {
        return [
            // Usado apenas na rejeição; ignorado na aprovação.
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'review_notes.max' => 'A observação não pode ter mais de 1000 caracteres.',
        ];
    }
}
