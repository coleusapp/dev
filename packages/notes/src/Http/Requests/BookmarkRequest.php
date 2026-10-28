<?php

namespace Coleus\Notes\Http\Requests;

use Coleus\Notes\Models\Bookmark;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Creating a note/heading bookmark (heading) or editing one (label, group).
 */
class BookmarkRequest extends FormRequest
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
            'heading' => ['nullable', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:'.Bookmark::MAX_LABEL_LENGTH],
            'group' => ['nullable', 'string', 'max:'.Bookmark::MAX_GROUP_LENGTH],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'label.max' => 'Bookmark names can be at most '.Bookmark::MAX_LABEL_LENGTH.' characters.',
            'group.max' => 'Group names can be at most '.Bookmark::MAX_GROUP_LENGTH.' characters.',
        ];
    }

    public function text(string $key): ?string
    {
        $value = trim((string) $this->validated($key));

        return $value === '' ? null : $value;
    }
}
