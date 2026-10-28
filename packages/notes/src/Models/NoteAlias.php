<?php

namespace Coleus\Notes\Models;

use Coleus\Notes\NotesModelDefaults;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Another name a note answers to in [[wiki links]], from its "aliases"
 * property. Kept in sync from the front matter by Services\NoteAliases;
 * reach them through the note so its user scope applies.
 *
 * @property int $id
 * @property int $note_id
 * @property string $alias
 * @property string $alias_lower
 * @property-read Note $note
 *
 * @mixin \Eloquent
 */
class NoteAlias extends NotesModelDefaults
{
    public $timestamps = false;

    protected $fillable = [
        'alias',
        'alias_lower',
    ];

    /**
     * @return BelongsTo<Note, $this>
     */
    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }
}
