<?php

namespace Coleus\Notes\Http\Requests;

use Closure;
use Coleus\Notes\Models\Folder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * List or gallery (A1.4), for one folder or as the default.
 */
class LayoutRequest extends FormRequest
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
            'layout' => ['required', Rule::in(['list', 'gallery'])],
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
            'layout.in' => 'Show the notes as a list or a gallery.',
        ];
    }

    public function folder(): ?Folder
    {
        $folder = $this->validated('folder_id');

        return $folder === null ? null : Folder::query()->findOrFail((int) $folder);
    }
}
