<?php

namespace Coleus\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\NoReturn;

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
    }

    public function getTable(): string
    {
        $prefix = config(static::$tablePrefix);
        if (!$prefix || !is_string($prefix) || Str::startsWith(parent::getTable(), $prefix)) {
            return parent::getTable();
        }

        return config(static::$tablePrefix).parent::getTable();
    }
}
