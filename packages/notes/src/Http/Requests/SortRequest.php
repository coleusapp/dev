<?php

namespace Coleus\Notes\Http\Requests;

use Coleus\Notes\Enums\NoteSort;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SortRequest extends FormRequest
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
            'sort' => ['required', Rule::enum(NoteSort::class)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sort.enum' => 'Sort by modified or created date.',
            'direction.in' => 'Sort ascending or descending.',
        ];
    }
}
