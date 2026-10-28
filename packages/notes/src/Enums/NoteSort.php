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
    case Title = 'title';

    /**
     * @param  Builder<Note>  $query
     * @return Builder<Note>
     */
    public function apply(Builder $query, string $direction): Builder
    {
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        $query->orderByRaw('case when pinned_at is null then 1 else 0 end');

        match ($this) {
            self::Modified => $query->orderBy('updated_at', $direction),
            self::Created => $query->orderBy('created_at', $direction),
            // Untitled notes sort after titled ones either way.
            self::Title => $query->orderByRaw("case when title = '' then 1 else 0 end")->orderByRaw("lower(title) {$direction}"),
        };

        return $query->orderBy('id', $direction);
    }
}
