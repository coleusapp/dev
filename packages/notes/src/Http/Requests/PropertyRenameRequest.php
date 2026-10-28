<?php

namespace Coleus\Notes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PropertyRenameRequest extends FormRequest
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
            'from' => ['required', 'string', 'max:255'],
            'to' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9_][A-Za-z0-9 _.-]*$/', 'different:from'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'to.required' => 'Give the property a new name.',
            'to.regex' => 'Use letters, numbers, spaces, "_", "-" or "." (not starting with a space, "-" or ".").',
            'to.different' => 'That is already its name.',
        ];
    }
}
