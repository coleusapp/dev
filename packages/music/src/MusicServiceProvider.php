<?php

namespace Coleus\Music;

use Coleus\Music\Http\Middleware\HandleInertiaRequests;
use Illuminate\Support\Str;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MusicServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('music')
            ->hasConfigFile()
            ->hasMigrations([
                //
            ])
            ->runsMigrations()
            ->hasRoute('web')
            ->hasAssets()
            ->hasViews();
    }

    public function bootingPackage(): void
    {
        if (Str::of(request()?->path())->startsWith(config('music.route_prefix'))) {
            app('router')
                ->pushMiddlewareToGroup('web', HandleInertiaRequests::class);
        }
    }

    public function packageRegistered(): void
    {
        $this->app->bind('music', function($app) {
            return new Music();
        });
    }
}
