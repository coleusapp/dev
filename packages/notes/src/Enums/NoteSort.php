<?php

namespace Coleus\Notes\Enums;

use Coleus\Notes\Models\Note;
use Illuminate\Database\Eloquent\Builder;

/**
 * How the note list is ordered (after pinned notes, which always come first).
 */
enum NoteSort: string
{
    case Modified = 'modified';
    case Created = 'created';

    /**
     * @param  Builder<Note>  $query
     * @return Builder<Note>
     */
    public function apply(Builder $query, string $direction): Builder
    {
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        return $query
            ->orderByRaw('case when pinned_at is null then 1 else 0 end')
            ->orderBy($this === self::Created ? 'created_at' : 'updated_at', $direction)
            ->orderBy('id', $direction);
    }
}
