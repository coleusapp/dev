<?php

namespace Coleus\Music;

use Coleus\Music\Commands\GetMusicFilesCommand;
use Coleus\Music\Http\Middleware\HandleInertiaRequests;
use Coleus\Music\Models\App as MusicApp;
use Illuminate\Support\Str;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MusicServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('music')
            ->hasConfigFile()
            ->hasRoute('web')
            ->hasAssets()
            ->hasViews()
            ->hasCommands([
                GetMusicFilesCommand::class,
            ]);
    }

    public function bootingPackage(): void
    {
        $this->loadMigrationsFrom($this->package->basePath('/../database/migrations'));

        if (Str::of(request()?->path())->startsWith(config('music.route_prefix'))) {
            app('router')
                ->pushMiddlewareToGroup('web', HandleInertiaRequests::class);
        }
    }

    public function packageRegistered(): void
    {
        $this->app->bind('music', function ($app) {
            return new Music;
        });
        $this->app->singleton('app.music', fn () => new MusicApp()->get());
    }
}
