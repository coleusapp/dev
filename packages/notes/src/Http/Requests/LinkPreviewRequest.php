<?php

namespace Coleus\Notes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The link to preview: a resolved note's id (reading view links carry it),
 * or a title to resolve like a link would (visual editor chips).
 */
class LinkPreviewRequest extends FormRequest
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
            'note' => ['nullable', 'integer'],
            'title' => ['required_without:note', 'nullable', 'string', 'max:255', 'not_regex:/[\[\]\n]/'],
            'from' => ['nullable', 'integer'],
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
}
