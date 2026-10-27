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
    public function list(): Collection
    {
        return $this->defaultQuery()->get();
    }
}
