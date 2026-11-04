<?php

namespace Coleus\Notes\Services;

use Coleus\Notes\Data\NoteData;
use Coleus\Notes\Enums\NoteSort;
use Coleus\Notes\Enums\NoteView;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Preferences\NotePreferences;
use Coleus\Support\Services\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends Service<Note, NoteData>
 */
class NoteService extends Service
{
    protected $model = Note::class;

    protected $data = NoteData::class;

    /**
     * @return Builder<Note>
     */
    public function defaultQuery(): Builder
    {
        return NoteSort::Modified->apply(Note::query(), 'desc');
    }

    /**
     * The order the list uses (Settings / sort menu).
     *
     * @return array{sort: NoteSort, direction: string}
     */
    public function sort(): array
    {
        return [
            'sort' => NoteSort::tryFrom((string) NotePreferences::get('list.sort')) ?? NoteSort::Modified,
            'direction' => NotePreferences::get('list.sort_direction') === 'asc' ? 'asc' : 'desc',
        ];
    }

    /**
     * @return Collection<int, Note>
     */
    public function list(?string $search = null, NoteView $view = NoteView::Notes): Collection
    {
        $order = $this->sort();

        return $view->scope($order['sort']->apply(Note::query(), $order['direction']))
            ->when($search !== null, fn (Builder $query) => $query->search((string) $search))
            ->get();
    }

    /**
     * How many notes each sidebar list holds.
     *
     * @return array{notes: int, bookmarks: int, archive: int, trash: int}
     */
    public function counts(): array
    {
        return [
            'notes' => NoteView::Notes->scope(Note::query())->count(),
            'bookmarks' => NoteView::Bookmarks->scope(Note::query())->count(),
            'archive' => NoteView::Archive->scope(Note::query())->count(),
            'trash' => NoteView::Trash->scope(Note::query())->count(),
        ];
    }

    /**
     * Moves a note to the trash.
     *
     * @param  Note  $model
     */
    public function destroy(mixed $model): ?bool
    {
        return $model->delete();
    }

    public function restore(Note $note): void
    {
        $note->restore();
    }

    public function forceDelete(Note $note): void
    {
        $note->forceDelete();
    }

    /**
     * Deletes every note in the current user's trash for good.
     *
     * @return int how many notes were deleted
     */
    public function emptyTrash(): int
    {
        $trashed = Note::query()->onlyTrashed()->get();

        $trashed->each(fn (Note $note) => $note->forceDelete());

        return $trashed->count();
    }

    /**
     * Pinning, bookmarking and archiving file a note without counting as an
     * edit, so they leave updated_at (and the list order) alone.
     */
    public function mark(Note $note, string $column, bool $on): void
    {
        if (($note->{$column} !== null) === $on) {
            return;
        }

        Note::withoutTimestamps(fn () => $note->forceFill([$column => $on ? now() : null])->save());
    }

    /**
     * Escapes LIKE wildcards with "!", which needs no quoting on MariaDB or SQLite
     * (unlike a backslash).
     */
    public static function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }
}
