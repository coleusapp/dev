<?php

namespace Coleus\Notes\Models;

use Coleus\Notes\NotesModelDefaults;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A snapshot of a note's body. Owned through its note: always reach
 * versions via $note->versions() so the note's user scope and policy apply.
 *
 * @property int $id
 * @property int $note_id
 * @property string $title
 * @property string $body
 * @property string|null $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Note $note
 *
 * @mixin \Eloquent
 */
class NoteVersion extends NotesModelDefaults
{
    protected $fillable = [
        'title',
        'body',
        'name',
    ];

    /**
     * @return BelongsTo<Note, $this>
     */
    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class)->withTrashed();
    }
}
