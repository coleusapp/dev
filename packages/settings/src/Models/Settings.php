<?php

namespace Coleus\Settings\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }
}
