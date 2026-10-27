<?php

namespace Coleus\Notes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NoteVersionRequest extends FormRequest
{
    public const int MAX_NAME_LENGTH = 100;

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
            'name' => ['present', 'nullable', 'string', 'max:'.self::MAX_NAME_LENGTH],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.max' => 'Version names can be at most '.self::MAX_NAME_LENGTH.' characters.',
        ];
    }

    public function versionName(): ?string
    {
        $name = trim((string) $this->validated('name'));

        return $name === '' ? null : $name;
    }
}
