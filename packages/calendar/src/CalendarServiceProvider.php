<?php

namespace Coleus\Calendar;

use Coleus\Calendar\Http\Middleware\HandleInertiaRequests;
use Illuminate\Support\Str;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CalendarServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('calendar')
            ->hasConfigFile()
            ->hasRoute('web')
            ->hasAssets()
            ->hasViews();
    }

    public function bootingPackage(): void
    {
        if (Str::of(request()?->path())->startsWith(config('calendar.route_prefix'))) {
            app('router')
                ->pushMiddlewareToGroup('web', HandleInertiaRequests::class);
        }
    }

    public function packageRegistered(): void
    {
        $this->app->bind('calendar', function ($app) {
            return new Calendar;
        });
    }
}
