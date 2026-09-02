<?php

namespace Coleus\Settings;

use Coleus\Settings\Models\Settings;

class SettingsGroup
{
    public function __construct(protected string $group) {}

    public function get(string $name, mixed $default = null): mixed
    {
        $setting = Settings::query()
            ->where('group', $this->group)
            ->where('name', $name)
            ->first();

        return $setting?->value ?? $default;
    }

    public function set(string $name, mixed $value): void
    {
        Settings::query()->updateOrCreate(
            ['group' => $this->group, 'name' => $name],
            ['value' => $value],
        );
    }

    public function has(string $name): bool
    {
        return Settings::query()
            ->where('group', $this->group)
            ->where('name', $name)
            ->exists();
    }

    public function forget(string $name): void
    {
        Settings::query()
            ->where('group', $this->group)
            ->where('name', $name)
            ->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Settings::query()
            ->where('group', $this->group)
            ->get(['name', 'value'])
            ->pluck('value', 'name')
            ->all();
    }
}
