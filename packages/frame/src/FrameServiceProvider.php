<?php

namespace Coleus\Frame;

use Coleus\Frame\Commands\FetchFrameArtworksCommand;
use Coleus\Frame\Http\Middleware\HandleInertiaRequests;
use Coleus\Frame\Models\Frame;
use Coleus\Frame\Policies\FramePolicy;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FrameServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('frame')
            ->hasConfigFile()
            ->discoversMigrations()
            ->runsMigrations()
            ->hasRoute('web')
            ->hasAssets()
            ->hasViews()
            ->hasCommand(FetchFrameArtworksCommand::class);
    }

    public function bootingPackage(): void
    {
        Gate::policy(Frame::class, FramePolicy::class);

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
}
