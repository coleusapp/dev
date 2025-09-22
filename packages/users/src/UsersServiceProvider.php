<?php

namespace Coleus\Users;

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
            ->hasMigrations([
                'create_teams_table',
                'create_model_has_users_table',
                'create_model_has_teams_table',
            ])
            ->runsMigrations()
            ->hasRoute('web')
            ->hasAssets()
            ->hasViews();
    }

    public function bootingPackage(): void
    {
        // config_class_alias('auth.providers.users.model', 'Coleus\Users\Models\UserAlias');
        function get_parent() {
            return config('auth.providers.users.model');
        }
        class_alias(get_parent(), 'Coleus\Users\Models\UserAlias');

        if (Str::of(request()?->path())->startsWith([config('users.route_prefix'), 'login', 'register'])) {
            app('router')
                ->pushMiddlewareToGroup('web', HandleInertiaRequests::class);
        }
    }
}
