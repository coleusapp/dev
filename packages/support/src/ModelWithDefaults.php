<?php

namespace Coleus\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
abstract class ModelWithDefaults extends Model
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
