<?php

namespace Coleus\Notes\Http\Requests;

use Closure;
use Coleus\Notes\Enums\NoteSort;
use Coleus\Notes\Models\Folder;
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
            'sort' => ['nullable', Rule::enum(NoteSort::class)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
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
            'sort.enum' => 'Sort by modified date, created date or title.',
            'direction.in' => 'Sort ascending or descending.',
        ];
    }

    public function folder(): ?Folder
    {
        $folder = $this->validated('folder_id');

        return $folder === null ? null : Folder::query()->findOrFail((int) $folder);
    }
}
