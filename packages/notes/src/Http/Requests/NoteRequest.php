<?php

namespace Coleus\Notes\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class NoteRequest extends FormRequest
{
    public const int MaxBodyLength = 1_000_000;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'body' => ['present', 'nullable', 'string', 'max:'.self::MaxBodyLength],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'body.present' => 'The note body is missing.',
            'body.max' => 'This note is too long to save.',
        ];
    }
}
