<?php

namespace Coleus\Music\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Music\Facades\Music;
use Coleus\Music\Http\Requests\GenreRequest;
use Coleus\Music\Http\Resources\GenreResource;
use Coleus\Music\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GenreController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('genres/Index', [
            'collection' => GenreResource::collection(Music::genre()->index()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('genres/Create');
    }

    public function store(GenreRequest $request): RedirectResponse
    {
        return to_route('music.genres.edit', [
            'genre' => Music::genre()->store($request),
        ]);
    }

    public function edit(Genre $genre): Response
    {
        return Inertia::render('genres/Edit', [
            'resource' => GenreResource::make($genre),
        ]);
    }

    public function update(GenreRequest $request, Genre $genre): RedirectResponse
    {
        Music::genre()->update($genre, $request->validated());

        return to_route('music.genres.edit', ['genre' => $genre]);
    }

    public function destroy(Genre $genre): RedirectResponse
    {
        Music::genre()->destroy($genre);

        return back();
    }
}
