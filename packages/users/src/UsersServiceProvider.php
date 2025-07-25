<?php

namespace Coleus\Users;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class UsersServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('users')
            ->hasMigrations([
                'create_teams_table',
                'create_model_has_users_table',
                'create_model_has_teams_table'
            ]);
    }
}
