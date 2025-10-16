<?php

namespace Coleus\Support;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @mixin \Illuminate\Database\Eloquent\Relations\Pivot
 */
abstract class PivotWithDefaults extends Pivot
{
    public static ?string $tablePrefix = null;

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (!is_array($this->guarded)) {
            $this->guarded = [];
        }
        $this->guarded[] = $this->primaryKey;

        $prefix = config(static::$tablePrefix, '');
        $this->table = (is_string($prefix) ? $prefix : '').$this->getTable() ?: parent::getTable();
    }
}
