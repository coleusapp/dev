<?php

namespace Coleus\Apps;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AppsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('apps')
            ->discoversMigrations()
            ->runsMigrations();
    }
}
