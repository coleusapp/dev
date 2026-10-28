<?php

namespace Coleus\Notes\Services;

use Coleus\Notes\Data\NoteData;
use Coleus\Notes\Enums\NoteSort;
use Coleus\Notes\Enums\NoteView;
use Coleus\Notes\Models\Folder;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Preferences\NotePreferences;
use Coleus\Support\Services\Service;
use Coleus\Users\Models\Scopes\UserScope;
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
     * The order the list uses: the folder's own sort when it has one,
     * otherwise the user's default (Settings / list menu).
     *
     * @return array{sort: NoteSort, direction: string, scope: 'folder'|'default'}
     */
    public function sortFor(?Folder $folder): array
    {
        if ($folder?->sort !== null && ($sort = NoteSort::tryFrom($folder->sort)) !== null) {
            return ['sort' => $sort, 'direction' => $folder->sort_direction === 'asc' ? 'asc' : 'desc', 'scope' => 'folder'];
        }

        return [
            'sort' => NoteSort::tryFrom((string) NotePreferences::get('list.sort')) ?? NoteSort::Modified,
            'direction' => NotePreferences::get('list.sort_direction') === 'asc' ? 'asc' : 'desc',
            'scope' => 'default',
        ];
    }

    /**
     * Moves a note to the trash. Its tags stay linked so a restore brings
     * them back, but tags only trashed notes use drop out of the sidebar.
     *
     * @param  Note  $model
     */
    public function destroy(mixed $model): ?bool
    {
        $tagIds = $model->tags()->allRelatedIds()->all();

        $deleted = $model->delete();

        NoteTags::deleteUnused($tagIds);

        return $deleted;
    }

    public function restore(Note $note): void
    {
        $note->restore();

        NoteTags::sync($note);
    }

    public function forceDelete(Note $note): void
    {
        $tagIds = $note->tags()->allRelatedIds()->all();

        $note->forceDelete();

        NoteTags::deleteUnused($tagIds);
    }

    /**
     * Permanently deletes every note in the current user's trash.
     *
     * @return int how many notes were deleted
     */
    public function emptyTrash(): int
    {
        $trashed = Note::query()->onlyTrashed()->get();

        $trashed->each(fn (Note $note) => $this->forceDelete($note));

        return $trashed->count();
    }

    /**
     * Permanently deletes every user's notes that were trashed more than
     * $days days ago. Runs from the scheduler, where there is no signed-in
     * user, so the user scope is lifted deliberately here and only here.
     *
     * @return int how many notes were deleted
     */
    public function purgeTrash(int $days): int
    {
        $purged = 0;

        Note::query()
            ->withoutGlobalScope(UserScope::class)
            ->onlyTrashed()
            ->where('deleted_at', '<', now()->subDays($days))
            ->chunkById(100, function ($notes) use (&$purged) {
                foreach ($notes as $note) {
                    $tagIds = $note->tags()->withoutGlobalScope(UserScope::class)->allRelatedIds()->all();
                    $note->forceDelete();
                    NoteTags::deleteUnusedForAllUsers($tagIds);
                    $purged++;
                }
            });

        return $purged;
    }

    /**
     * A new note with the same text, in the same folder. It's a fresh
     * note: not pinned, not archived, with its own history and dates;
     * tags follow from the copied text.
     */
    public function duplicate(Note $note): Note
    {
        $copy = $this->store(['body' => $note->body]);

        if ($note->folder_id !== null) {
            Note::withoutTimestamps(fn () => $copy->forceFill(['folder_id' => $note->folder_id])->save());
        }

        return $copy;
    }

    /**
     * How many notes each sidebar list holds.
     *
     * @return array{notes: int, archive: int, trash: int}
     */
    public function counts(): array
    {
        return [
            'notes' => NoteView::Notes->scope(Note::query())->count(),
            'archive' => NoteView::Archive->scope(Note::query())->count(),
            'trash' => NoteView::Trash->scope(Note::query())->count(),
        ];
    }

    /**
     * Remembers when a note was last opened (for Recents), without
     * counting as an edit.
     */
    public function markOpened(Note $note): void
    {
        Note::withoutTimestamps(fn () => $note->forceFill(['opened_at' => now()])->saveQuietly());
    }

    /**
     * Pinning is not an edit, so it leaves updated_at (and the list order within pinned notes) alone.
     */
    public function pin(Note $note): void
    {
        if ($note->pinned_at === null) {
            Note::withoutTimestamps(fn () => $note->forceFill(['pinned_at' => now()])->save());
        }
    }

    public function unpin(Note $note): void
    {
        Note::withoutTimestamps(fn () => $note->forceFill(['pinned_at' => null])->save());
    }

    /**
     * Like pinning, archiving files a note away without counting as an edit.
     */
    public function archive(Note $note): void
    {
        if ($note->archived_at === null) {
            Note::withoutTimestamps(fn () => $note->forceFill(['archived_at' => now()])->save());
        }
    }

    public function unarchive(Note $note): void
    {
        Note::withoutTimestamps(fn () => $note->forceFill(['archived_at' => null])->save());
    }

    /**
     * @return Collection<int, Note>
     */
    public function list(?string $tag = null, ?string $search = null, NoteView $view = NoteView::Notes, ?int $folderId = null): Collection
    {
        $order = $this->sortFor($folderId === null ? null : Folder::query()->find($folderId));

        return $view->scope($order['sort']->apply(Note::query(), $order['direction']))
            ->when($folderId !== null, fn (Builder $query) => $query->where('folder_id', $folderId))
            ->when($tag !== null, function (Builder $query) use ($tag) {
                foreach (explode(',', (string) $tag) as $each) {
                    $this->whereTagged($query, $each);
                }
            })
            ->when($search !== null, fn (Builder $query) => $query->search((string) $search))
            ->get();
    }

    /**
     * Notes tagged with $tag or any tag nested under it.
     *
     * @param  Builder<Note>  $query
     * @return Builder<Note>
     */
    public function whereTagged(Builder $query, string $tag): Builder
    {
        return $query->whereHas('tags', fn (Builder $tags) => $tags->where(fn (Builder $names) => $names
            ->where('name', $tag)
            ->orWhereRaw("name like ? escape '!'", [self::escapeLike($tag).'/%'])));
    }

    /**
     * Escapes LIKE wildcards with "!", which needs no quoting on MariaDB or SQLite
     * (unlike a backslash). "_" matters: it's valid inside a tag.
     */
    public static function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }

    /**
     * @return list<array{name: string, path: string, count: int, children: list<mixed>}>
     */
    public function tagTree(): array
    {
        return TagTree::build(NoteView::Notes->scope(Note::query()));
    }
}
