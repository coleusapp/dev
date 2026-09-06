<?php

namespace Coleus\Apps;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AppsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('apps')
            ->hasMigrations([
                'apps/create_apps_table',
                'apps/create_model_has_apps_table',
            ])
            ->runsMigrations();
    }
}
