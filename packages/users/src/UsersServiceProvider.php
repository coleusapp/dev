<?php

namespace Coleus\Users;

use Coleus\Users\Console\Commands\ManageUsers;
use Coleus\Users\Http\Middleware\HandleInertiaRequests;
use Illuminate\Support\Str;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class UsersServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('users')
            ->hasConfigFile()
            ->hasCommand(ManageUsers::class)
            ->hasRoute('web')
            ->hasAssets()
            ->hasViews();
    }

    public function bootingPackage(): void
    {
        $this->loadMigrationsFrom($this->package->basePath('/../database/migrations'));

        if (!class_exists('Coleus\Users\Models\UserAlias')) {
            class_alias($this->getUserAliasClass(), 'Coleus\Users\Models\UserAlias');
        }

        if (Str::of(request()?->path())->startsWith([config('users.route_prefix'), 'login', 'register'])) {
            app('router')
                ->pushMiddlewareToGroup('web', HandleInertiaRequests::class);
        }
    }

    public function getUserAliasClass(): string
    {
        return config('auth.providers.users.model');
    }
}
