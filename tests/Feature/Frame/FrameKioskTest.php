<?php

use Coleus\Frame\Facades\Frames;
use Coleus\Frame\Models\Frame;
use Coleus\Frame\Models\FrameArtwork;
use Coleus\Frame\Services\KioskService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(config('frame.disk'));
    $this->token = Frames::kiosk()->generateToken();
    $this->frame = Frame::factory()->withToken($this->token)->create(['interval_seconds' => 90]);
});

it('serves the kiosk page for a valid token with private headers and no session', function () {
    $response = $this->get(route('frame.kiosk.show', ['token' => $this->token]))
        ->assertOk()
        ->assertSee(route('frame.kiosk.next', ['token' => $this->token], false), false)
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    expect($response->headers->get('Cache-Control'))->toContain('no-store')
        ->and($response->headers->getCookies())->toBeEmpty();
});

it('returns a plain 404 for an unknown, hashed or inactive token', function (string $route, string $kind) {
    $token = match ($kind) {
        'unknown' => Frames::kiosk()->generateToken(),
        'hash instead of token' => substr($this->frame->token_hash, 0, 48),
        'inactive' => tap(Frames::kiosk()->generateToken(), fn (string $token) => Frame::factory()->inactive()->withToken($token)->create()),
    };

    $this->get(route($route, ['token' => $token, 'artwork' => 1]))
        ->assertNotFound()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
})->with([
    'page' => 'frame.kiosk.show',
    'next' => 'frame.kiosk.next',
    'image' => 'frame.kiosk.image',
])->with(['unknown', 'hash instead of token', 'inactive']);

it('does not match a token of the wrong shape', function () {
    $this->get('/'.config('frame.route_prefix').'/short-token/next')->assertNotFound();
});

it('returns the next artwork and marks it shown', function () {
    $this->freezeSecond();
    $artwork = FrameArtwork::factory()->for($this->frame)->create([
        'title' => 'Water Lilies', 'artist_display' => 'Claude Monet', 'date_display' => '1906',
    ]);

    $this->getJson(route('frame.kiosk.next', ['token' => $this->token]))
        ->assertOk()
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertExactJson([
            'id' => $artwork->id,
            'image_url' => route('frame.kiosk.image', ['token' => $this->token, 'artwork' => $artwork->id], false),
            'title' => 'Water Lilies',
            'artist' => 'Claude Monet',
            'date' => '1906',
            'interval_seconds' => 90,
        ]);

    expect($artwork->refresh()->last_shown_at->equalTo(now()))->toBeTrue();
});

it('returns 204 when the frame has no artworks', function () {
    $this->getJson(route('frame.kiosk.next', ['token' => $this->token]))->assertNoContent();
});

it('shows never-shown artworks before shown ones', function () {
    $neverShown = FrameArtwork::factory()->for($this->frame)->count(KioskService::RotationWindow)->create();
    FrameArtwork::factory()->for($this->frame)->shownAt(now()->subYear())->create();

    $next = $this->getJson(route('frame.kiosk.next', ['token' => $this->token]))->json('id');

    expect($neverShown->pluck('id'))->toContain($next);
})->repeat(5);

it('picks among the least recently shown artworks only', function () {
    $artworks = FrameArtwork::factory()->for($this->frame)->count(KioskService::RotationWindow + 5)
        ->sequence(fn ($sequence) => ['last_shown_at' => now()->subHours(100 - $sequence->index)])
        ->create();

    $next = $this->getJson(route('frame.kiosk.next', ['token' => $this->token]))->json('id');

    expect($artworks->take(KioskService::RotationWindow)->pluck('id'))->toContain($next);
})->repeat(5);

it('does not repeat the current artwork when another is available', function () {
    $artworks = FrameArtwork::factory()->for($this->frame)->count(2)->create();

    $next = $this->getJson(route('frame.kiosk.next', ['token' => $this->token, 'current' => $artworks[0]->id]))->json('id');

    expect($next)->toBe($artworks[1]->id);
});

it('shows the current artwork again when it is the only one', function () {
    $artwork = FrameArtwork::factory()->for($this->frame)->create();

    $this->getJson(route('frame.kiosk.next', ['token' => $this->token, 'current' => $artwork->id]))
        ->assertOk()
        ->assertJsonPath('id', $artwork->id);
});

it('ignores artworks of other frames', function () {
    FrameArtwork::factory()->create();

    $this->getJson(route('frame.kiosk.next', ['token' => $this->token]))->assertNoContent();
});

it('streams the frame own artwork image with a long private cache', function () {
    $artwork = FrameArtwork::factory()->for($this->frame)->create(['path' => 'frame/1/1.jpg']);
    Storage::disk(config('frame.disk'))->put('frame/1/1.jpg', 'jpeg-bytes');

    $response = $this->get(route('frame.kiosk.image', ['token' => $this->token, 'artwork' => $artwork->id]))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg');

    expect($response->streamedContent())->toBe('jpeg-bytes')
        ->and($response->headers->get('Cache-Control'))->toContain('max-age=31536000')
        ->and($response->headers->get('Cache-Control'))->toContain('private');
});

it('refuses another frame artwork image', function () {
    $other = FrameArtwork::factory()->create(['path' => 'frame/2/1.jpg']);
    Storage::disk(config('frame.disk'))->put('frame/2/1.jpg', 'jpeg-bytes');

    $this->get(route('frame.kiosk.image', ['token' => $this->token, 'artwork' => $other->id]))->assertNotFound();
});

it('returns 404 when the image file is missing', function () {
    $artwork = FrameArtwork::factory()->for($this->frame)->create();

    $this->get(route('frame.kiosk.image', ['token' => $this->token, 'artwork' => $artwork->id]))->assertNotFound();
});

it('throttles the kiosk', function () {
    config(['frame.kiosk_rate_limit' => '120,1']);

    foreach (range(1, 120) as $ignored) {
        $this->getJson(route('frame.kiosk.next', ['token' => $this->token]))->assertNoContent();
    }

    $this->getJson(route('frame.kiosk.next', ['token' => $this->token]))->assertTooManyRequests();
});
