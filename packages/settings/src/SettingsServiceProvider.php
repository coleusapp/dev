<?php

namespace Coleus\Settings;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SettingsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('settings')
            ->discoversMigrations()
            ->runsMigrations();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton('settings', fn () => new SettingsManager);
    }
}
