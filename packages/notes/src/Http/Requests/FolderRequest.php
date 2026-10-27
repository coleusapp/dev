<?php

namespace Coleus\Notes\Http\Requests;

use Closure;
use Coleus\Notes\Models\Folder;
use Coleus\Notes\Services\FolderService;
use Illuminate\Foundation\Http\FormRequest;

class FolderRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:'.Folder::MAX_NAME_LENGTH,
                'not_regex:/[\/\\\\]/',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (is_string($value) && app(FolderService::class)->nameTaken(trim($value), $this->parentId(), $this->exceptFolderId())) {
                        $fail('There is already a folder with that name here.');
                    }
                },
            ],
            'parent_id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, Closure $fail) {
                    if ($value !== null && ! Folder::query()->whereKey($value)->exists()) {
                        $fail('That folder does not exist.');
                    }
                },
                function (string $attribute, mixed $value, Closure $fail) {
                    $folder = $this->route('folder');

                    if ($value !== null && $folder instanceof Folder && in_array((int) $value, app(FolderService::class)->subtreeIds($folder), true)) {
                        $fail('A folder cannot go inside itself.');
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
            'name.required' => 'Give the folder a name.',
            'name.max' => 'Folder names can be at most '.Folder::MAX_NAME_LENGTH.' characters.',
            'name.not_regex' => 'Folder names cannot contain slashes.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    public function parentId(): ?int
    {
        $parent = $this->input('parent_id');

        return is_numeric($parent) ? (int) $parent : null;
    }

    /**
     * When renaming or moving, the folder itself doesn't clash with its own name.
     */
    protected function exceptFolderId(): ?int
    {
        $folder = $this->route('folder');

        return $folder instanceof Folder ? $folder->id : null;
    }

    public function parent(): ?Folder
    {
        return $this->parentId() === null ? null : Folder::query()->findOrFail($this->parentId());
    }
}
