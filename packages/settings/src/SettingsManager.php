<?php

namespace Coleus\Settings;

use Illuminate\Database\Eloquent\Model;

class SettingsManager
{
    public function forApp(Model|int|string|null $app = null): SettingsScope
    {
        return new SettingsScope()->forApp($app);
    }

    public function forUser(Model|int|string|null $user = null): SettingsScope
    {
        return new SettingsScope()->forUser($user);
    }

    public function get(string $name, mixed $default = null): mixed
    {
        return new SettingsScope()->get($name, $default);
    }

    public function set(string $name, mixed $value): void
    {
        new SettingsScope()->set($name, $value);
    }

    public function has(string $name): bool
    {
        return new SettingsScope()->has($name);
    }

    public function forget(string $name): void
    {
        new SettingsScope()->forget($name);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return new SettingsScope()->all();
    }
}
