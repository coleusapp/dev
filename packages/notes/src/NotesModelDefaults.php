<?php

namespace Coleus\Notes;

use Coleus\Support\ModelWithDefaults;
use Illuminate\Database\Eloquent\Builder;

/**
 * @method static Builder<static>|NotesModelDefaults newModelQuery()
 * @method static Builder<static>|NotesModelDefaults newQuery()
 * @method static Builder<static>|NotesModelDefaults query()
 *
 * @mixin \Eloquent
 */
class NotesModelDefaults extends ModelWithDefaults
{
    public static ?string $tablePrefix = 'notes.table_prefix';
}
