<?php

namespace Coleus\Settings;

use Coleus\Settings\Models\Settings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class SettingsScope
{
    protected bool $scopedToApp = false;

    protected int|string|null $appId = null;

    protected bool $scopedToUser = false;

    protected int|string|null $userId = null;

    public function forApp(Model|int|string|null $app = null): static
    {
        $scope = clone $this;
        $scope->scopedToApp = true;
        $scope->appId = $app instanceof Model ? $app->getKey() : $app;

        return $scope;
    }

    public function forUser(Model|int|string|null $user = null): static
    {
        $scope = clone $this;
        $scope->scopedToUser = true;
        $scope->userId = $user instanceof Model ? $user->getKey() : ($user ?? auth()->id());

        return $scope;
    }

    public function get(string $name, mixed $default = null): mixed
    {
        $userId = $this->scopedToUser ? $this->userId : auth()->id();

        if ($userId) {
            $override = $this->find($name, $userId);

            if ($override) {
                return $override->value;
            }
        }

        return $this->find($name, null)?->value ?? $default;
    }

    public function set(string $name, mixed $value): void
    {
        $userId = $this->resolveWriteUserId();

        $setting = $this->find($name, $userId) ?? new Settings(['name' => $name]);
        $setting->value = $value;
        $setting->save();

        if ($this->scopedToApp && $this->appId) {
            $setting->apps()->sync([$this->appId]);
        }

        if ($userId) {
            $setting->users()->sync([$userId]);
        }
    }

    public function has(string $name): bool
    {
        $userId = $this->scopedToUser ? $this->userId : auth()->id();

        if ($userId && $this->find($name, $userId)) {
            return true;
        }

        return (bool) $this->find($name, null);
    }

    public function forget(string $name): void
    {
        $userId = $this->resolveWriteUserId();

        $setting = $this->find($name, $userId);

        $setting?->apps()->detach();
        $setting?->users()->detach();
        $setting?->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $defaults = $this->scopeQuery(null)->get(['id', 'name', 'value'])->pluck('value', 'name');

        $userId = $this->scopedToUser ? $this->userId : auth()->id();

        if (! $userId) {
            return $defaults->all();
        }

        $overrides = $this->scopeQuery($userId)->get(['id', 'name', 'value'])->pluck('value', 'name');

        return $defaults->merge($overrides)->all();
    }

    protected function resolveWriteUserId(): int|string|null
    {
        if (! $this->scopedToUser) {
            return null;
        }

        if (! $this->userId) {
            throw new RuntimeException('Cannot write a user-scoped setting without an authenticated or specified user.');
        }

        return $this->userId;
    }

    protected function find(string $name, int|string|null $userId): ?Settings
    {
        return $this->scopeQuery($userId)->where('name', $name)->first();
    }

    protected function scopeQuery(int|string|null $userId): Builder
    {
        $query = Settings::query();

        if ($this->scopedToApp) {
            $appId = $this->appId;

            $query->when(
                ! $appId,
                fn (Builder $q) => $q->whereDoesntHave('apps'),
                fn (Builder $q) => $q->whereHas('apps', fn (Builder $sub) => $sub->whereKey($appId)),
            );
        }

        $query->when(
            ! $userId,
            fn (Builder $q) => $q->whereDoesntHave('users'),
            fn (Builder $q) => $q->whereHas('users', fn (Builder $sub) => $sub->whereKey($userId)),
        );

        return $query;
    }
}
