<?php

namespace Coleus\Notes\Enums;

use Coleus\Notes\Models\Note;
use Illuminate\Database\Eloquent\Builder;

enum NoteView: string
{
    case Notes = 'notes';
    case Trash = 'trash';

    /**
     * @param  Builder<Note>  $query
     * @return Builder<Note>
     */
    public function scope(Builder $query): Builder
    {
        return match ($this) {
            self::Notes => $query,
            self::Trash => $query->onlyTrashed(),
        };
    }
}
