<?php

namespace Coleus\Notes\Http\Requests;

use Coleus\Notes\Enums\NoteView;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NoteListRequest extends FormRequest
{
    public const int MaxSearchLength = 200;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:'.self::MaxSearchLength],
            'view' => ['nullable', Rule::enum(NoteView::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'q.max' => 'That search is too long.',
            'view.enum' => 'That list does not exist.',
        ];
    }
}
