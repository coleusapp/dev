<?php

namespace Coleus\Settings\Facades;

use Coleus\Settings\SettingsGroup;
use Illuminate\Support\Facades\Facade;

/**
 * @method static SettingsGroup group(string $group)
 */
class Settings extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'settings';
    }
}
