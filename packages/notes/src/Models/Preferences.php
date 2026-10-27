<?php

namespace Coleus\Notes\Models;

use Coleus\Notes\NotesModelDefaults;

/**
 * One row per user holding their Notes preferences. Read and write through
 * Preferences\NotePreferences, which owns the keys, defaults and validation.
 *
 * @property int $id
 * @property int $user_id
 * @property array<string, mixed> $values
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @mixin \Eloquent
 */
class Preferences extends NotesModelDefaults
{
    protected $table = 'preferences';

    protected $fillable = [
        'user_id',
        'values',
    ];

    public function casts(): array
    {
        return [
            'values' => 'array',
        ];
    }
}
