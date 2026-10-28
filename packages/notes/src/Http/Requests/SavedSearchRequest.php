<?php

namespace Coleus\Notes\Http\Requests;

use Coleus\Notes\Models\Bookmark;

/**
 * Saving the current list filters (same rules as the list itself) under a name.
 */
class SavedSearchRequest extends NoteListRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'label' => ['required', 'string', 'max:'.Bookmark::MAX_LABEL_LENGTH],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'label.required' => 'Give the search a name.',
            'label.max' => 'Names can be at most '.Bookmark::MAX_LABEL_LENGTH.' characters.',
        ];
    }

    /**
     * The filters worth keeping: only the ones that are set (and not the default view).
     *
     * @return array<string, string|int>
     */
    public function savedFilters(): array
    {
        return array_filter($this->filters(), fn (mixed $value, string $key) => $value !== null && $value !== '' && ! ($key === 'view' && $value === 'notes'), ARRAY_FILTER_USE_BOTH);
    }
}
