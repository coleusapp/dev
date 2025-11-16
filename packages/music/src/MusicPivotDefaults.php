<?php

namespace Coleus\Music;

use Coleus\Support\PivotWithDefaults;

/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicPivotDefaults newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicPivotDefaults newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicPivotDefaults query()
 * @mixin \Eloquent
 */
class MusicPivotDefaults extends PivotWithDefaults
{
    public static ?string $tablePrefix = 'music.table_prefix';
}
