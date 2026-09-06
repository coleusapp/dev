<?php

namespace Coleus\Settings\Facades;

use Coleus\Settings\SettingsScope;
use Illuminate\Support\Facades\Facade;

/**
 * @method static SettingsScope forApp(\Illuminate\Database\Eloquent\Model|int|string|null $app = null)
 * @method static SettingsScope forUser(\Illuminate\Database\Eloquent\Model|int|string|null $user = null)
 * @method static mixed get(string $name, mixed $default = null)
 * @method static void set(string $name, mixed $value)
 * @method static bool has(string $name)
 * @method static void forget(string $name)
 * @method static array all()
 */
class Settings extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'settings';
    }
}
