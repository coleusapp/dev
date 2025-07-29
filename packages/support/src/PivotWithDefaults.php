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

        $this->guarded[] = $this->primaryKey;
        $this->table = config(static::$tablePrefix).$this->getTable() ?: parent::getTable();
    }
}
