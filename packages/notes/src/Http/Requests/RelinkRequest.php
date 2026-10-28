<?php

namespace Coleus\Notes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The old title whose links should follow a renamed note.
 */
class RelinkRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255', 'not_regex:/[\[\]\n]/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.not_regex' => 'A linked note title cannot contain brackets or line breaks.',
        ];
    }

    public function oldTitle(): string
    {
        return (string) $this->validated('title');
    }
}
