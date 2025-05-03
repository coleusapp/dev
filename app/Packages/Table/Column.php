<?php

namespace App\Packages\Table;

class Column
{
    private static $column = [
        //
    ];

    public static function make($column)
    {
        static::$column = [

        ];

        return new static();
    }
}
