<?php

use Coleus\Frame\Data\ArtworkData;
use Coleus\Frame\Sources\ClevelandMuseumOfArtSource;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/** @param  list<array<string, mixed>>  $artworks */
function fakeCleveland(array $artworks, ?int $total = null): void
{
    Http::preventStrayRequests();

    Http::fake([
        config('frame.cleveland.api_url').'/artworks/*' => fn (Request $request) => Http::response([
            'info' => ['total' => $total ?? count($artworks)],
            'data' => $request['limit'] === 1 ? array_slice($artworks, 0, 1) : $artworks,
        ]),
    ]);
}

function clevelandArtwork(int $id, array $overrides = []): array
{
    return [
        'id' => $id,
        'title' => "Painting {$id}",
        'creation_date' => 'c. 1915',
        'creators' => [['description' => "Artist {$id} (French, 1840–1926)"]],
        'images' => [
            'web' => ['url' => "https://openaccess-cdn.clevelandart.org/{$id}/{$id}_web.jpg"],
            'print' => ['url' => "https://openaccess-cdn.clevelandart.org/{$id}/{$id}_print.jpg"],
        ],
        ...$overrides,
    ];
}

it('returns paintings preferring the print image over the web image', function () {
    fakeCleveland([
        clevelandArtwork(1),
        clevelandArtwork(2, ['images' => ['web' => ['url' => 'https://openaccess-cdn.clevelandart.org/2/2_web.jpg'], 'print' => null]]),
        clevelandArtwork(3, ['images' => null]),
        clevelandArtwork(4, ['title' => '', 'creators' => []]),
    ]);

    $artworks = new ClevelandMuseumOfArtSource()->search(null, 4);

    expect($artworks->all())->toEqual([
        new ArtworkData(1, 'Painting 1', 'Artist 1 (French, 1840–1926)', 'c. 1915', 'https://openaccess-cdn.clevelandart.org/1/1_print.jpg'),
        new ArtworkData(2, 'Painting 2', 'Artist 2 (French, 1840–1926)', 'c. 1915', 'https://openaccess-cdn.clevelandart.org/2/2_web.jpg'),
        new ArtworkData(4, 'Untitled', null, 'c. 1915', 'https://openaccess-cdn.clevelandart.org/4/4_print.jpg'),
    ]);
});

it('searches CC0 paintings with an image and sends the query only when given', function (?string $query) {
    fakeCleveland([clevelandArtwork(1)]);

    new ClevelandMuseumOfArtSource()->search($query, 5);

    Http::assertSent(fn (Request $request) => $request['limit'] === 5
        && $request['type'] === 'Painting'
        && $request['has_image'] === 1
        && $request['cc0'] === 1
        && ($request->data()['q'] ?? null) === $query);
})->with([
    'with a query' => 'monet',
    'without a query' => null,
]);

it('keeps the random skip inside the results', function () {
    fakeCleveland([clevelandArtwork(1)], total: 30);

    new ClevelandMuseumOfArtSource()->search(null, 20);

    Http::assertSent(fn (Request $request) => $request['limit'] === 20 && $request['skip'] >= 0 && $request['skip'] <= 10);
});

it('returns nothing without a second search when nothing matches', function () {
    fakeCleveland([]);

    expect(new ClevelandMuseumOfArtSource()->search('nothing', 20))->toBeEmpty();
    Http::assertSentCount(1);
});

it('throws when the search fails', function () {
    Http::preventStrayRequests();
    Http::fake([config('frame.cleveland.api_url').'/artworks/*' => Http::response('', 503)]);

    new ClevelandMuseumOfArtSource()->search(null, 20);
})->throws(RequestException::class);

it('downloads the image bytes', function () {
    Http::preventStrayRequests();
    Http::fake(['https://openaccess-cdn.clevelandart.org/1/1_print.jpg' => Http::response('jpeg-bytes')]);

    expect(new ClevelandMuseumOfArtSource()->image(new ArtworkData(1, 'Painting', null, null, 'https://openaccess-cdn.clevelandart.org/1/1_print.jpg')))
        ->toBe('jpeg-bytes');
});
