<?php

namespace Coleus\Music\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Music\Facades\Music;
use Coleus\Music\Http\Requests\ArtistRequest;
use Coleus\Music\Http\Resources\ArtistResource;
use Coleus\Music\Models\Artist;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ArtistController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('artists/Index', [
            'collection' => ArtistResource::collection(Music::artist()->index()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('artists/Create');
    }

    public function store(ArtistRequest $request): RedirectResponse
    {
        return to_route('music.artists.edit', [
            'artist' => Music::artist()->store($request),
        ]);
    }

    public function edit(Artist $artist): Response
    {
        return Inertia::render('artists/Edit', [
            'resource' => ArtistResource::make($artist),
        ]);
    }

    public function update(ArtistRequest $request, Artist $artist): RedirectResponse
    {
        Music::artist()->update($artist, $request->validated());

        return to_route('music.artists.edit', ['artist' => $artist]);
    }

    public function destroy(Artist $artist): RedirectResponse
    {
        Music::artist()->destroy($artist);

        return back();
    }
}
