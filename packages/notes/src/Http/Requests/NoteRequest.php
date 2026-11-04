<?php

namespace Coleus\Notes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NoteRequest extends FormRequest
{
    public const int MAX_BODY_LENGTH = 1_000_000;

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
            'body' => ['present', 'nullable', 'string', 'max:'.self::MAX_BODY_LENGTH],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.present' => 'The note body is missing.',
            'body.max' => 'This note is too long to save.',
        ];
    }

    /**
     * @return array{body: string}
     */
    public function noteData(): array
    {
        return ['body' => (string) $this->validated('body')];
    }
}
