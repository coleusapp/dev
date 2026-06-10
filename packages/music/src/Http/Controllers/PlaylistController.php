<?php

namespace Coleus\Music\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Music\Facades\Music;
use Coleus\Music\Http\Requests\PlaylistRequest;
use Coleus\Music\Http\Resources\PlaylistResource;
use Coleus\Music\Models\Playlist;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PlaylistController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('playlists/Index', [
            'collection' => PlaylistResource::collection(Music::playlist()->index()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('playlists/Create', [
            'tracks' => Music::track()->options(),
        ]);
    }

    public function store(PlaylistRequest $request): RedirectResponse
    {
        return to_route('music.playlists.edit', [
            'playlist' => Music::playlist()->store($request->validated()),
        ]);
    }

    public function edit(Playlist $playlist): Response
    {
        return Inertia::render('playlists/Edit', [
            'resource' => PlaylistResource::make($playlist->load('tracks')),
            'tracks' => Music::track()->options(),
        ]);
    }

    public function update(PlaylistRequest $request, Playlist $playlist): RedirectResponse
    {
        Music::playlist()->update($playlist, $request->validated());

        return to_route('music.playlists.edit', ['playlist' => $playlist]);
    }

    public function destroy(Playlist $playlist): RedirectResponse
    {
        Music::playlist()->destroy($playlist);

        return back();
    }
}
