<?php

namespace Coleus\Music;

use Coleus\Music\Commands\GetMusicFilesCommand;
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
            ->hasMigrations(array_map(
                fn ($migration) => config('music.name')."/$migration",
                [
                    'create_genres_table',
                    'create_artists_table',
                    'create_albums_table',
                    'create_tracks_table',
                    'create_playlists_table',
                    'create_playlist_track_table',
                ]))
            ->runsMigrations()
            ->hasRoute('web')
            ->hasAssets()
            ->hasViews()
            ->hasCommands([
                GetMusicFilesCommand::class,
            ]);
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
        $this->app->bind('music', function ($app) {
            return new Music;
        });
    }
}
