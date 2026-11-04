<?php

namespace Coleus\Notes\Http\Requests;

use Coleus\Notes\Enums\NoteView;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The list filters carried in the query string of the notes pages.
 */
class NoteListRequest extends FormRequest
{
    public const int MAX_SEARCH_LENGTH = 200;

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
            'q' => ['nullable', 'string', 'max:'.self::MAX_SEARCH_LENGTH],
            'view' => ['nullable', Rule::enum(NoteView::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'q.max' => 'That search is too long.',
            'view.enum' => 'That list does not exist.',
        ];
    }

    public function search(): ?string
    {
        $search = trim((string) $this->validated('q'));

        return $search === '' ? null : $search;
    }

    public function view(): NoteView
    {
        return NoteView::tryFrom((string) $this->validated('view')) ?? NoteView::Notes;
    }

    /**
     * @return array{q: string|null, view: string}
     */
    public function filters(): array
    {
        return [
            'q' => $this->search(),
            'view' => $this->view()->value,
        ];
    }
}
