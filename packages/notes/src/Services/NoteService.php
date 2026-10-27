<?php

namespace Coleus\Notes\Services;

use Coleus\Notes\Data\NoteData;
use Coleus\Notes\Models\Note;
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
        return Note::query()
            ->orderByRaw('case when pinned_at is null then 1 else 0 end')
            ->orderByDesc('updated_at')
            ->orderByDesc('id');
    }

    /**
     * @param  Note  $model
     */
    public function destroy(mixed $model): ?bool
    {
        $tagIds = $model->tags()->allRelatedIds()->all();

        $deleted = $model->delete();

        NoteTags::deleteUnused($tagIds);

        return $deleted;
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
     * @return Collection<int, Note>
     */
    public function list(?string $tag = null, ?string $search = null): Collection
    {
        return $this->defaultQuery()
            ->when($tag !== null, fn (Builder $query) => $this->whereTagged($query, (string) $tag))
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
        return TagTree::build(Note::query());
    }
}
