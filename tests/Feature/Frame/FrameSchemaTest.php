<?php

use Coleus\Frame\Facades\Frames;
use Coleus\Frame\Models\Frame;
use Coleus\Frame\Models\FrameArtwork;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Schema;

it('creates the frames and artworks tables', function () {
    expect(Schema::getColumnListing(app(Frame::class)->getTable()))->toEqualCanonicalizing([
        'id', 'name', 'token_hash', 'search_query', 'sources', 'interval_seconds', 'is_active', 'created_at', 'updated_at',
    ])->and(Schema::getColumnListing(app(FrameArtwork::class)->getTable()))->toEqualCanonicalizing([
        'id', 'frame_id', 'source', 'source_id', 'title', 'artist_display', 'date_display', 'path', 'last_shown_at', 'created_at', 'updated_at',
    ]);
});

it('hashes tokens with sha256 and never stores the plain token', function () {
    $token = Frames::kiosk()->generateToken();
    $frame = Frame::factory()->withToken($token)->create();

    expect(strlen($token))->toBe(48)
        ->and($frame->token_hash)->toBe(hash('sha256', $token))
        ->and($frame->token_hash)->not->toContain($token);
});

it('defaults to a 60 second interval and active', function () {
    $frame = Frame::factory()->create()->refresh();

    expect($frame->interval_seconds)->toBe(60)->and($frame->is_active)->toBeTrue();
});

it('allows an artwork only once per frame and source', function () {
    $frame = Frame::factory()->create();
    FrameArtwork::factory()->for($frame)->create(['source' => 'aic', 'source_id' => 42]);

    FrameArtwork::factory()->for($frame)->create(['source' => 'aic', 'source_id' => 42]);
})->throws(UniqueConstraintViolationException::class);

it('allows the same source id from different sources in one frame', function () {
    $frame = Frame::factory()->create();
    FrameArtwork::factory()->for($frame)->create(['source' => 'aic', 'source_id' => 42]);
    FrameArtwork::factory()->for($frame)->create(['source' => 'met', 'source_id' => 42]);

    expect($frame->artworks()->where('source_id', 42)->count())->toBe(2);
});

it('allows the same artwork in different frames', function () {
    FrameArtwork::factory()->create(['source_id' => 42]);
    FrameArtwork::factory()->create(['source_id' => 42]);

    expect(FrameArtwork::query()->where('source_id', 42)->count())->toBe(2);
});

it('deletes artworks with their frame', function () {
    $artwork = FrameArtwork::factory()->create();

    $artwork->frame()->withoutGlobalScopes()->first()->delete();

    expect(FrameArtwork::query()->count())->toBe(0);
});
