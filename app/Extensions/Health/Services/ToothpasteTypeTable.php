<?php

namespace App\Extensions\Health\Services;

use App\Extensions\Health\Models\ToothpasteType;
use App\Packages\Table\Table;
use Illuminate\Database\Eloquent\Builder;

class ToothpasteTypeTable extends Table
{
    public static function query(): Builder
    {
        return ToothpasteType::query()
            ->orderBy('created_at', 'desc');
    }
}