<?php

namespace Coleus\Notes\Http\Requests;

use Closure;
use Coleus\Notes\Enums\NoteView;
use Coleus\Notes\Markdown\Hashtags;
use Coleus\Notes\Models\Folder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The list filters carried in the query string of the notes pages.
 */
class NoteListRequest extends FormRequest
{
    public const int MAX_TAGS = 5;

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
            'tag' => [
                'nullable',
                'string',
                'max:'.(self::MAX_TAGS * (Hashtags::MAX_LENGTH + 1)),
                function (string $attribute, mixed $value, Closure $fail) {
                    $tags = is_string($value) ? array_filter(explode(',', $value), fn (string $tag) => trim($tag) !== '') : [];

                    if (count($tags) > self::MAX_TAGS) {
                        $fail('Filter by at most '.self::MAX_TAGS.' tags at a time.');
                    } elseif (collect($tags)->contains(fn (string $tag) => mb_strlen(trim($tag)) > Hashtags::MAX_LENGTH)) {
                        $fail('That tag is too long.');
                    }
                },
            ],
            'q' => ['nullable', 'string', 'max:'.self::MAX_SEARCH_LENGTH],
            'view' => ['nullable', Rule::enum(NoteView::class)],
            'folder' => [
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
            'tag.max' => 'That tag is too long.',
            'q.max' => 'That search is too long.',
            'view.enum' => 'That list does not exist.',
        ];
    }

    /**
     * The tags to filter by: "?tag=work,urgent" means notes tagged with all
     * of them (each including its nested tags). Normalized like hashtags.
     *
     * @return list<string>
     */
    public function tags(): array
    {
        return collect(explode(',', (string) $this->validated('tag')))
            ->map(fn (string $tag) => trim(mb_strtolower($tag), "/# \t"))
            ->filter(fn (string $tag) => $tag !== '')
            ->unique()
            ->values()
            ->all();
    }

    public function tag(): ?string
    {
        $tags = $this->tags();

        return $tags === [] ? null : implode(',', $tags);
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

    public function folderId(): ?int
    {
        $folder = $this->validated('folder');

        return $folder === null ? null : (int) $folder;
    }

    /**
     * @return array{tag: string|null, q: string|null, view: string, folder: int|null}
     */
    public function filters(): array
    {
        return ['tag' => $this->tag(), 'q' => $this->search(), 'view' => $this->view()->value, 'folder' => $this->folderId()];
    }
}
