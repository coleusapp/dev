<?php

namespace Coleus\Notes;

use Coleus\Support\ModelWithDefaults;

/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotesModelDefaults newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotesModelDefaults newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotesModelDefaults query()
 *
 * @mixin \Eloquent
 */
class NotesModelDefaults extends ModelWithDefaults
{
    public static ?string $tablePrefix = 'notes.table_prefix';
}
