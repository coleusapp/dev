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
        return Note::query()->orderByDesc('updated_at')->orderByDesc('id');
    }

    /**
     * @return Collection<int, Note>
     */
    public function list(): Collection
    {
        return $this->defaultQuery()->get();
    }
}
