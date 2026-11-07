<?php

namespace Coleus\Notes\Services;

use Coleus\Notes\Data\NoteData;
use Coleus\Notes\Data\NoteListData;
use Coleus\Notes\Enums\NoteView;
use Coleus\Notes\Models\Note;
use Coleus\Support\Services\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/** @extends Service<Note, NoteData> */
class NoteService extends Service
{
    protected $model = Note::class;

    protected $data = NoteData::class;

    /** @return Builder<Note> */
    public function defaultQuery(): Builder
    {
        return Note::query()
            ->orderByRaw('case when pinned_at is null then 1 else 0 end')
            ->orderByDesc('updated_at')
            ->orderByDesc('id');
    }

    /** @param  array<string, mixed>  $payload */
    public function filters(array $payload): NoteListData
    {
        return NoteListData::from($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{q?: string, view?: string}
     */
    public function listQuery(array $payload): array
    {
        $filters = $this->filters($payload);

        return array_filter([
            'q' => $filters->q,
            'view' => $filters->view === NoteView::Notes ? null : $filters->view->value,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return Collection<int, Note>
     */
    public function list(array $payload): Collection
    {
        $filters = $this->filters($payload);

        return $filters->view->scope($this->defaultQuery())
            ->when($filters->q, fn (Builder $query) => $this->search($query, $filters->q))
            ->get();
    }

    /**
     * @param  Builder<Note>  $query
     * @return Builder<Note>
     */
    private function search(Builder $query, string $terms): Builder
    {
        return $query->whereLike('body', "%$terms%");
    }

    /** @return array{notes: int, trash: int} */
    public function counts(): array
    {
        return [
            'notes' => NoteView::Notes->scope(Note::query())->count(),
            'trash' => NoteView::Trash->scope(Note::query())->count(),
        ];
    }

    public function pin(Note $note): void
    {
        $note->pinned_at ??= now();

        Note::withoutTimestamps(fn () => $note->save());
    }

    public function unpin(Note $note): void
    {
        $note->pinned_at = null;

        Note::withoutTimestamps(fn () => $note->save());
    }
}
