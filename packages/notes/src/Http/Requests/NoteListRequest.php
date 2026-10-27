<?php

namespace Coleus\Notes\Http\Requests;

use Coleus\Notes\Markdown\Hashtags;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The list filters carried in the query string of the notes pages.
 */
class NoteListRequest extends FormRequest
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
            'tag' => ['nullable', 'string', 'max:'.Hashtags::MAX_LENGTH],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tag.max' => 'That tag is too long.',
        ];
    }

    public function tag(): ?string
    {
        $tag = trim(mb_strtolower((string) $this->validated('tag')), "/# \t");

        return $tag === '' ? null : $tag;
    }

    /**
     * @return array{tag: string|null}
     */
    public function filters(): array
    {
        return ['tag' => $this->tag()];
    }
}
