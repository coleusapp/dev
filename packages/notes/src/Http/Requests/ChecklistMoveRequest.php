<?php

namespace Coleus\Notes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChecklistMoveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'line' => ['required', 'integer', 'min:1'],
            'before_line' => ['present', 'nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'line.required' => 'Say which checklist item to move.',
            'before_line.present' => 'Say where to move it (or null for the end of the list).',
        ];
    }
}
