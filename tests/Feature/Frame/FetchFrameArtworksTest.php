<?php

use Coleus\Frame\Contracts\ArtworkSource;
use Coleus\Frame\Data\ArtworkData;
use Coleus\Frame\Facades\Frames;
use Coleus\Frame\Jobs\FetchFrameArtworks;
use Coleus\Frame\Models\Frame;
use Coleus\Frame\Models\FrameArtwork;
use Coleus\Frame\Sources\ArtInstituteOfChicagoSource;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;

function frameJpeg(int $width, int $height): string
{
    $image = imagecreatetruecolor($width, $height);
    ob_start();
    imagejpeg($image);

    return (string) ob_get_clean();
}

/**
 * @param  list<array<string, mixed>>  $artworks
 * @param  array<string, string|int>  $images  image_id => JPEG bytes or HTTP status
 */
function fakeAic(array $artworks, array $images = [], ?int $total = null): void
{
    Http::fake(function (Request $request) use ($artworks, $images, $total) {
        if (str_contains($request->url(), '/artworks/search')) {
            return Http::response([
                'pagination' => ['total' => $total ?? count($artworks)],
                'data' => $request['limit'] === 1 ? array_slice($artworks, 0, 1) : $artworks,
            ]);
        }

        preg_match('#/iiif/2/([^/]+)/full/1686,/0/default\.jpg$#', $request->url(), $matches);
        $image = $images[$matches[1] ?? ''] ?? frameJpeg(1686, 1124);

        return is_int($image) ? Http::response('', $image) : Http::response($image, 200, ['Content-Type' => 'image/jpeg']);
    });
}

function aicArtwork(int $id, array $overrides = []): array
{
    return [
        'id' => $id,
        'title' => "Painting {$id}",
        'artist_display' => "Artist {$id}",
        'date_display' => '1890',
        'image_id' => "img-{$id}",
        ...$overrides,
    ];
}

class FirstFakeArtworkSource implements ArtworkSource
{
    public function key(): string
    {
        return 'first';
    }

    public function name(): string
    {
        return ucfirst($this->key());
    }

    public function search(?string $query, int $count): Collection
    {
        return collect([new ArtworkData(1, "{$this->key()} 1", null, null, "https://{$this->key()}.test/1.jpg")]);
    }

    public function image(ArtworkData $artwork): string
    {
        return frameJpeg(100, 100);
    }
}

class SecondFakeArtworkSource extends FirstFakeArtworkSource
{
    public function key(): string
    {
        return 'second';
    }
}

beforeEach(function () {
    Storage::fake(config('frame.disk'));
    Sleep::fake();
    config(['frame.sources' => [ArtInstituteOfChicagoSource::class]]);
});

it('stores resized artworks with their metadata', function () {
    $frame = Frame::factory()->create();
    fakeAic([aicArtwork(1), aicArtwork(2)], ['img-2' => frameJpeg(1000, 3000)]);

    FetchFrameArtworks::dispatchSync($frame);

    $artworks = $frame->artworks()->orderBy('source_id')->get();
    expect($artworks)->toHaveCount(2)
        ->and($artworks[0]->only('title', 'artist_display', 'date_display', 'path'))->toBe([
            'title' => 'Painting 1',
            'artist_display' => 'Artist 1',
            'date_display' => '1890',
            'path' => "frame/{$frame->id}/aic/1.jpg",
        ]);

    $landscape = getimagesizefromstring(Storage::disk(config('frame.disk'))->get("frame/{$frame->id}/aic/1.jpg"));
    $portrait = getimagesizefromstring(Storage::disk(config('frame.disk'))->get("frame/{$frame->id}/aic/2.jpg"));

    expect([$landscape[0], $landscape[1], $landscape['mime']])->toBe([1620, 1080, 'image/jpeg'])
        ->and([$portrait[0], $portrait[1]])->toBe([360, 1080]);

    Sleep::assertSleptTimes(2);
});

it('never upscales small images', function () {
    $frame = Frame::factory()->create();
    fakeAic([aicArtwork(1)], ['img-1' => frameJpeg(800, 600)]);

    FetchFrameArtworks::dispatchSync($frame);

    $size = getimagesizefromstring(Storage::disk(config('frame.disk'))->get("frame/{$frame->id}/aic/1.jpg"));
    expect([$size[0], $size[1]])->toBe([800, 600]);
});

it('searches public domain paintings with an image and sends the AIC user agent', function () {
    $frame = Frame::factory()->create(['search_query' => 'monet']);
    fakeAic([aicArtwork(1)]);

    FetchFrameArtworks::dispatchSync($frame, 5);

    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/artworks/search')
        && $request['limit'] === 5
        && $request['q'] === 'monet'
        && $request['query']['bool']['filter'] === [
            ['term' => ['is_public_domain' => true]],
            ['term' => ['artwork_type_title.keyword' => 'Painting']],
            ['exists' => ['field' => 'image_id']],
        ]
        && $request->hasHeader('AIC-User-Agent', config('frame.aic.user_agent')));
});

it('omits the search text when the frame has no query', function () {
    $frame = Frame::factory()->create(['search_query' => null]);
    fakeAic([aicArtwork(1)]);

    FetchFrameArtworks::dispatchSync($frame);

    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/artworks/search') && !isset($request['q']));
});

it('keeps the random page inside the 10,000 result window', function () {
    $frame = Frame::factory()->create();
    fakeAic([aicArtwork(1)], total: 500_000);

    FetchFrameArtworks::dispatchSync($frame, 20);

    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/artworks/search')
        && $request['limit'] === 20
        && $request['page'] >= 1
        && $request['page'] * 20 <= 10_000);
});

it('skips artworks the frame already has', function () {
    $frame = Frame::factory()->create();
    FrameArtwork::factory()->for($frame)->create(['source_id' => 1, 'title' => 'Kept']);
    fakeAic([aicArtwork(1), aicArtwork(2)]);

    FetchFrameArtworks::dispatchSync($frame);

    expect($frame->artworks()->pluck('title', 'source_id')->all())->toBe([1 => 'Kept', 2 => 'Painting 2']);
    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '/img-1/'));
});

it('skips results without an image id', function () {
    $frame = Frame::factory()->create();
    fakeAic([aicArtwork(1, ['image_id' => null])]);

    FetchFrameArtworks::dispatchSync($frame);

    expect($frame->artworks()->count())->toBe(0);
});

it('does nothing when the search finds no artworks', function () {
    $frame = Frame::factory()->create();
    fakeAic([]);

    FetchFrameArtworks::dispatchSync($frame);

    expect($frame->artworks()->count())->toBe(0);
    Http::assertSentCount(1);
});

it('logs and skips an image that fails to download or decode', function (string|int $failure) {
    Log::spy();
    $frame = Frame::factory()->create();
    fakeAic([aicArtwork(1), aicArtwork(2)], ['img-1' => $failure]);

    FetchFrameArtworks::dispatchSync($frame);

    expect($frame->artworks()->pluck('source_id')->all())->toBe([2]);
    Storage::disk(config('frame.disk'))->assertMissing("frame/{$frame->id}/aic/1.jpg");
    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message, array $context) => $context['source_id'] === 1);
})->with([
    'server error' => 500,
    'not found' => 404,
    'not an image' => 'not a jpeg',
]);

it('logs and skips an image too large to decode in the available memory', function () {
    Log::spy();
    $frame = Frame::factory()->create();
    fakeAic([aicArtwork(1), aicArtwork(2)], ['img-1' => frameJpeg(3000, 3000)]);
    $memoryLimit = ini_get('memory_limit');
    ini_set('memory_limit', (string) (memory_get_usage() + 32 * 1024 * 1024));

    try {
        FetchFrameArtworks::dispatchSync($frame);
    } finally {
        ini_set('memory_limit', $memoryLimit);
    }

    expect($frame->artworks()->pluck('source_id')->all())->toBe([2]);
    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message, array $context) => $context['source_id'] === 1
        && str_contains($context['error'], '3000x3000'));
});

it('fails the job when the search request fails', function () {
    $frame = Frame::factory()->create();
    Http::fake(['*' => Http::response('', 503)]);

    FetchFrameArtworks::dispatchSync($frame);
})->throws(RequestException::class);

it('prunes the oldest artworks and their files beyond the pool size', function () {
    config(['frame.pool_size' => 3]);
    $frame = Frame::factory()->create();
    $disk = Storage::disk(config('frame.disk'));

    $old = collect([10, 11])->map(function (int $sourceId, int $index) use ($frame, $disk) {
        $path = "frame/{$frame->id}/{$sourceId}.jpg";
        $disk->put($path, 'old');

        return FrameArtwork::factory()->for($frame)->create([
            'source_id' => $sourceId,
            'path' => $path,
            'created_at' => now()->subDays(10 - $index),
        ]);
    });
    fakeAic([aicArtwork(1), aicArtwork(2)]);

    FetchFrameArtworks::dispatchSync($frame);

    expect($frame->artworks()->orderBy('source_id')->pluck('source_id')->all())->toBe([1, 2, 11]);
    $disk->assertMissing($old[0]->path);
    $disk->assertExists($old[1]->path);
});

it('stores artworks from every configured source, each under its own key', function () {
    config(['frame.sources' => [FirstFakeArtworkSource::class, SecondFakeArtworkSource::class]]);
    $frame = Frame::factory()->create(['sources' => ['first', 'second']]);

    FetchFrameArtworks::dispatchSync($frame);

    expect($frame->artworks()->orderBy('source')->get(['source', 'source_id', 'title', 'path'])->toArray())->toBe([
        ['source' => 'first', 'source_id' => 1, 'title' => 'first 1', 'path' => "frame/{$frame->id}/first/1.jpg"],
        ['source' => 'second', 'source_id' => 1, 'title' => 'second 1', 'path' => "frame/{$frame->id}/second/1.jpg"],
    ]);
});

it('skips an artwork only when the frame has it from the same source', function () {
    config(['frame.sources' => [FirstFakeArtworkSource::class, SecondFakeArtworkSource::class]]);
    $frame = Frame::factory()->create(['sources' => ['first', 'second']]);
    FrameArtwork::factory()->for($frame)->create(['source' => 'first', 'source_id' => 1, 'title' => 'Kept']);

    FetchFrameArtworks::dispatchSync($frame);

    expect($frame->artworks()->orderBy('source')->pluck('title', 'source')->all())->toBe(['first' => 'Kept', 'second' => 'second 1']);
});

it('fetches only from the sources the frame has selected', function () {
    config(['frame.sources' => [FirstFakeArtworkSource::class, SecondFakeArtworkSource::class]]);
    $frame = Frame::factory()->create(['sources' => ['second', 'removed']]);

    FetchFrameArtworks::dispatchSync($frame);

    expect($frame->artworks()->pluck('source')->all())->toBe(['second']);
});

it('is unique per frame', function () {
    $frame = Frame::factory()->create();
    $job = new FetchFrameArtworks($frame);

    expect($job)->toBeInstanceOf(ShouldBeUnique::class)
        ->and($job->uniqueId())->toBe((string) $frame->id)
        ->and($job->timeout)->toBeGreaterThan(0);
});

it('removes the frame folder when the frame is deleted', function () {
    $frame = Frame::factory()->create();
    $other = Frame::factory()->create();
    $disk = Storage::disk(config('frame.disk'));
    $disk->put("frame/{$frame->id}/1.jpg", 'x');
    $disk->put("frame/{$other->id}/1.jpg", 'x');

    Frames::frame()->destroy($frame);

    $disk->assertMissing("frame/{$frame->id}");
    $disk->assertExists("frame/{$other->id}/1.jpg");
});
