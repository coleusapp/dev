<?php

namespace Coleus\Music;

use Coleus\Support\ModelWithDefaults;

/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicModelDefaults newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicModelDefaults newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicModelDefaults query()
 * @mixin \Eloquent
 */
class MusicModelDefaults extends ModelWithDefaults
{
    public static ?string $tablePrefix = 'music.table_prefix';
}
