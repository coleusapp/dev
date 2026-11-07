<?php

use Coleus\Frame\Data\ArtworkData;
use Coleus\Frame\Sources\MetropolitanMuseumOfArtSource;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * @param  list<int>  $objectIds
 * @param  array<int, array<string, mixed>|int>  $objects  objectID => object or HTTP status
 */
function fakeMet(array $objectIds, array $objects = [], ?int $total = null): void
{
    Http::preventStrayRequests();

    $api = config('frame.met.api_url');

    Http::fake([
        "{$api}/v1.1/search*" => fn (Request $request) => Http::response([
            'total' => $total ?? count($objectIds),
            'objectIDs' => $objectIds ? ($request['limit'] === 1 ? array_slice($objectIds, 0, 1) : $objectIds) : null,
        ]),
        "{$api}/v1/objects/*" => function (Request $request) use ($objects) {
            $object = $objects[(int) basename($request->url())] ?? 404;

            return is_int($object) ? Http::response(['message' => 'Not a valid object'], $object) : Http::response($object);
        },
    ]);
}

function metObject(int $id, array $overrides = []): array
{
    return [
        'objectID' => $id,
        'title' => "Painting {$id}",
        'artistDisplayName' => "Artist {$id}",
        'objectDate' => '1889',
        'primaryImage' => "https://images.metmuseum.org/CRDImages/ep/original/{$id}.jpg",
        'isPublicDomain' => true,
        ...$overrides,
    ];
}

it('returns public domain paintings with their primary image', function () {
    fakeMet([1, 2, 3, 4], [
        1 => metObject(1),
        2 => metObject(2, ['isPublicDomain' => false, 'primaryImage' => '']),
        3 => metObject(3, ['primaryImage' => '']),
        4 => metObject(4, ['title' => '']),
    ]);

    $artworks = new MetropolitanMuseumOfArtSource()->search(null, 4);

    expect($artworks->all())->toEqual([
        new ArtworkData(1, 'Painting 1', 'Artist 1', '1889', 'https://images.metmuseum.org/CRDImages/ep/original/1.jpg'),
        new ArtworkData(4, 'Untitled', 'Artist 4', '1889', 'https://images.metmuseum.org/CRDImages/ep/original/4.jpg'),
    ]);
});

it('skips an object whose details fail to load', function () {
    fakeMet([1, 2], [1 => 500, 2 => metObject(2)]);

    expect(new MetropolitanMuseumOfArtSource()->search(null, 2)->pluck('source_id')->all())->toBe([2]);
});

it('searches paintings with images and sends the query only when given', function (?string $query) {
    fakeMet([1], [1 => metObject(1)]);

    new MetropolitanMuseumOfArtSource()->search($query, 5);

    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/v1.1/search')
        && $request['limit'] === 5
        && $request['medium'] === 'Paintings'
        && $request['hasImages'] === 'true'
        && ($request->data()['q'] ?? null) === $query);
})->with([
    'with a query' => 'monet',
    'without a query' => null,
]);

it('keeps the random offset inside the 10,000 result window', function () {
    fakeMet([1], [1 => metObject(1)], total: 500_000);

    new MetropolitanMuseumOfArtSource()->search(null, 20);

    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/v1.1/search')
        && $request['limit'] === 20
        && $request['offset'] >= 0
        && $request['offset'] + 20 <= MetropolitanMuseumOfArtSource::MaxSearchWindow);
});

it('returns nothing without a second search when nothing matches', function () {
    fakeMet([]);

    expect(new MetropolitanMuseumOfArtSource()->search('nothing', 20))->toBeEmpty();
    Http::assertSentCount(1);
});

it('throws when the search fails', function () {
    Http::preventStrayRequests();
    Http::fake([config('frame.met.api_url').'/v1.1/search*' => Http::response('', 503)]);

    new MetropolitanMuseumOfArtSource()->search(null, 20);
})->throws(RequestException::class);

it('downloads the image bytes', function () {
    Http::preventStrayRequests();
    Http::fake(['https://images.metmuseum.org/CRDImages/ep/original/1.jpg' => Http::response('jpeg-bytes')]);

    expect(new MetropolitanMuseumOfArtSource()->image(new ArtworkData(1, 'Painting', null, null, 'https://images.metmuseum.org/CRDImages/ep/original/1.jpg')))
        ->toBe('jpeg-bytes');
});
