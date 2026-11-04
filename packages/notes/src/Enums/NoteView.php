<?php

namespace Coleus\Notes\Enums;

use Coleus\Notes\Models\Note;
use Illuminate\Database\Eloquent\Builder;

/**
 * Which set of notes the list shows.
 */
enum NoteView: string
{
    case Notes = 'notes';
    case Bookmarks = 'bookmarks';
    case Recent = 'recent';
    case Archive = 'archive';
    case Trash = 'trash';

    /**
     * How many notes the Recents list shows.
     */
    public const int RECENT_LIMIT = 30;

    /**
     * @param  Builder<Note>  $query
     * @return Builder<Note>
     */
    public function scope(Builder $query): Builder
    {
        return match ($this) {
            self::Notes => $query->whereNull('archived_at'),
            self::Bookmarks => $query->whereNotNull('bookmarked_at'),
            self::Recent => $query->whereNull('archived_at')->reorder()->orderByDesc('updated_at')->orderByDesc('id')->limit(self::RECENT_LIMIT),
            self::Archive => $query->whereNotNull('archived_at'),
            self::Trash => $query->onlyTrashed(),
        };
    }
}
