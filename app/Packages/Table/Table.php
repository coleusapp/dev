<?php

namespace App\Packages\Table;

use App\Packages\Table\Contracts\Columns;

abstract class Table implements Columns
{
    public abstract static function query();

    /**
     * @return array<string, array{
     *     label: string,
     *     sort: array<array{label: string, value: string}>
     * }>
     */
    public abstract static function columns(): array;
}
