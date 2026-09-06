<?php

namespace Coleus\Notes;

use Coleus\Notes\Http\Middleware\HandleInertiaRequests;
use Coleus\Notes\Models\App as NotesApp;
use Illuminate\Support\Str;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class NotesServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('notes')
            ->hasConfigFile()
            ->hasRoute('web')
            ->hasAssets()
            ->hasViews();
    }

    public function bootingPackage(): void
    {
        if (Str::of(request()?->path())->startsWith(config('notes.route_prefix'))) {
            app('router')
                ->pushMiddlewareToGroup('web', HandleInertiaRequests::class);
        }
    }

    public function packageRegistered(): void
    {
        $this->app->bind('notes', function ($app) {
            return new Notes;
        });
        $this->app->singleton('app.notes', fn () => new NotesApp()->get());
    }
}
