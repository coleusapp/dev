<?php

namespace Coleus\Frame;

use Coleus\Support\ModelWithDefaults;

/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FrameModelDefaults newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FrameModelDefaults newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FrameModelDefaults query()
 *
 * @mixin \Eloquent
 */
class FrameModelDefaults extends ModelWithDefaults
{
    public static ?string $tablePrefix = 'frame.table_prefix';
}
