<?php

namespace Coleus\Music\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Music\Facades\Music;
use Coleus\Music\Http\Requests\AlbumRequest;
use Coleus\Music\Http\Resources\AlbumResource;
use Coleus\Music\Models\Album;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AlbumController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('albums/Index', [
            'collection' => AlbumResource::collection(Music::album()->index()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('albums/Create', [
            'artists' => Music::artist()->options(),
        ]);
    }

    public function store(AlbumRequest $request): RedirectResponse
    {
        return to_route('music.albums.edit', [
            'album' => Music::album()->store($request),
        ]);
    }

    public function edit(Album $album): Response
    {
        return Inertia::render('albums/Edit', [
            'resource' => AlbumResource::make($album),
            'artists' => Music::artist()->options(),
        ]);
    }

    public function update(AlbumRequest $request, Album $album): RedirectResponse
    {
        Music::album()->update($album, $request->validated());

        return to_route('music.albums.edit', ['album' => $album]);
    }

    public function destroy(Album $album): RedirectResponse
    {
        Music::album()->destroy($album);

        return back();
    }
}
