<?php

namespace Coleus\Notes\Http\Requests;

use Closure;
use Coleus\Notes\Models\Folder;
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
            'folder_id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, Closure $fail) {
                    if ($value !== null && ! Folder::query()->whereKey($value)->exists()) {
                        $fail('That folder does not exist.');
                    }
                },
            ],
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

    /**
     * The folder a new note goes into (the one being viewed), if any.
     */
    public function folder(): ?Folder
    {
        $folder = $this->validated('folder_id');

        return $folder === null ? null : Folder::query()->findOrFail((int) $folder);
    }
}
