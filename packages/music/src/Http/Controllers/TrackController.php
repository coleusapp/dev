<?php

namespace Coleus\Music\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Music\Facades\Music;
use Coleus\Music\Http\Requests\TrackRequest;
use Coleus\Music\Http\Resources\TrackResource;
use Coleus\Music\Models\Track;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TrackController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('tracks/Index', [
            'collection' => TrackResource::collection(Music::track()->index()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('tracks/Create', [
            'artists' => Music::artist()->options(),
            'albums' => Music::album()->options(),
            'genres' => Music::genre()->options(),
        ]);
    }

    public function store(TrackRequest $request): RedirectResponse
    {
        return to_route('music.tracks.edit', [
            'track' => Music::track()->store($request),
        ]);
    }

    public function edit(Track $track): Response
    {
        return Inertia::render('tracks/Edit', [
            'resource' => TrackResource::make($track->load('artist', 'album', 'genre')),
            'artists' => Music::artist()->options(),
            'albums' => Music::album()->options(),
            'genres' => Music::genre()->options(),
        ]);
    }

    public function update(TrackRequest $request, Track $track): RedirectResponse
    {
        Music::track()->update($track, $request);

        return to_route('music.tracks.edit', ['track' => $track]);
    }

    public function destroy(Track $track): RedirectResponse
    {
        Music::track()->destroy($track);

        return back();
    }
}
