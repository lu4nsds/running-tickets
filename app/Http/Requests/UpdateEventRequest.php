<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'slug' => 'sometimes|required|string|max:255|unique:events,slug,' . $eventId,
            'description' => 'nullable|string',
            'city' => 'sometimes|required|string|max:100',
            'venue' => 'sometimes|required|string|max:255',
            'date_start' => 'sometimes|required|date',
            'date_end' => 'sometimes|required|date|after_or_equal:date_start',
            'max_participants' => 'nullable|integer|min:1',
            'banner_url' => 'nullable|url',
            'status' => 'sometimes|in:draft,published,cancelled',
            'meta' => 'nullable|array',
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
