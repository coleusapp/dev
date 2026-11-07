<?php

namespace Coleus\Frame;

use Coleus\Frame\Commands\FetchFrameArtworksCommand;
use Coleus\Frame\Http\Middleware\HandleInertiaRequests;
use Coleus\Frame\Services\ArtworkService;
use Coleus\Frame\Services\FrameService;
use Coleus\Frame\Services\KioskService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Str;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FrameServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('frame')
            ->hasConfigFile()
            ->hasRoute('web')
            ->hasAssets()
            ->hasViews()
            ->hasCommand(FetchFrameArtworksCommand::class);
    }

    public function bootingPackage(): void
    {
        $this->loadMigrationsFrom($this->package->basePath('/../database/migrations'));

        if (Str::of(request()?->path())->startsWith(config('frame.route_prefix'))) {
            app('router')
                ->pushMiddlewareToGroup('web', HandleInertiaRequests::class);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command(FetchFrameArtworksCommand::class)
                ->everySixHours()
                ->withoutOverlapping();
        });
    }

    public function packageRegistered(): void
    {
        $this->app->singleton('frames', fn () => new Frames);
        $this->app->singleton(FrameService::class);
        $this->app->singleton(KioskService::class);
        $this->app->singleton(ArtworkService::class);

        foreach (config('frame.sources') as $source) {
            $this->app->singleton($source);
        }
    }
}
