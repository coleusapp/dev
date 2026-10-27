<?php

namespace Coleus\Notes\Http\Requests;

use Closure;
use Coleus\Notes\Models\Folder;
use Illuminate\Foundation\Http\FormRequest;

class MoveNoteRequest extends FormRequest
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
            'folder_id' => [
                'present',
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
            'folder_id.present' => 'Say which folder to move the note to (or null for none).',
        ];
    }

    public function folder(): ?Folder
    {
        $folder = $this->validated('folder_id');

        return $folder === null ? null : Folder::query()->findOrFail((int) $folder);
    }
}
