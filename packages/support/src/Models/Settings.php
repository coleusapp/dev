<?php

namespace Coleus\Support\Models;

use Coleus\Users\Concerns\HasUser;
use Spatie\LaravelSettings\Models\SettingsProperty;

class Settings extends SettingsProperty
{
    use HasUser;

    public static function get(string $property)
    {
        return SettingsProperty::get($property);
    }
}
