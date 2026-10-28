<?php

namespace Coleus\Notes\Models;

use Coleus\Notes\NotesModelDefaults;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One front-matter property of a note, mirrored into a table so properties
 * can be listed and filtered across notes. Synced by Services\NoteProperties;
 * reach it through the note so the user scope applies.
 *
 * @property int $id
 * @property int $note_id
 * @property string $key
 * @property string $key_lower
 * @property string $type
 * @property mixed $value
 * @property string|null $value_text lowercased, list items joined by newlines, for matching
 * @property-read Note $note
 *
 * @mixin \Eloquent
 */
class NoteProperty extends NotesModelDefaults
{
    public $timestamps = false;

    protected $fillable = [
        'key',
        'key_lower',
        'type',
        'value',
        'value_text',
    ];

    public function casts(): array
    {
        return ['value' => 'array'];
    }

    /**
     * @return BelongsTo<Note, $this>
     */
    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }
}
