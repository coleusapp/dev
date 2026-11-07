<?php

use Coleus\Frame\Http\Controllers\FrameController;
use Coleus\Frame\Jobs\FetchFrameArtworks;
use Coleus\Frame\Models\Frame;
use Coleus\Frame\Models\FrameArtwork;
use Coleus\Users\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

function frameFormData(array $overrides = []): array
{
    return ['name' => 'Living room', 'search_query' => 'monet', 'interval_seconds' => 120, 'is_active' => true, ...$overrides];
}

function kioskTokenFrom(string $kioskUrl): string
{
    return basename(parse_url($kioskUrl, PHP_URL_PATH));
}

it('requires authentication', function (string $method, string $route) {
    $frame = Frame::factory()->create();
    auth()->logout();

    $this->{$method}(route($route, ['frame' => $frame]))->assertRedirect(route('login'));
})->with([
    'index' => ['get', 'frame.index'],
    'create' => ['get', 'frame.create'],
    'store' => ['post', 'frame.store'],
    'edit' => ['get', 'frame.edit'],
    'update' => ['put', 'frame.update'],
    'destroy' => ['delete', 'frame.destroy'],
    'regenerate token' => ['post', 'frame.token.regenerate'],
    'fetch' => ['post', 'frame.fetch'],
]);

it('lists only the current user frames with their artwork counts', function () {
    $mine = Frame::factory()->create(['name' => 'Office']);
    FrameArtwork::factory()->for($mine)->count(2)->create();
    $this->actingAs(User::factory()->create());
    Frame::factory()->create();
    $this->actingAs($this->user);

    $this->get(route('frame.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Index', false)
            ->has('frames', 1)
            ->where('frames.0', ['id' => $mine->id, 'name' => 'Office', 'is_active' => true, 'artworks_count' => 2]));
});

it('shows the create form', function () {
    $this->get(route('frame.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Edit', false)->where('frame', null)->where('kioskUrl', null));
});

it('creates a frame owned by the user and shows the kiosk URL exactly once', function () {
    $response = $this->post(route('frame.store'), frameFormData());

    $frame = Frame::query()->sole();
    $response->assertRedirect(route('frame.edit', ['frame' => $frame]));
    expect($frame->only('name', 'search_query', 'interval_seconds', 'is_active'))->toBe([
        'name' => 'Living room', 'search_query' => 'monet', 'interval_seconds' => 120, 'is_active' => true,
    ])->and($frame->isOwnedBy($this->user->id))->toBeTrue();

    $kioskUrl = null;
    $this->get(route('frame.edit', ['frame' => $frame]))
        ->assertInertia(function (Assert $page) use (&$kioskUrl) {
            $page->whereType('kioskUrl', 'string');
            $kioskUrl = $page->toArray()['props']['kioskUrl'];
        });

    $token = kioskTokenFrom($kioskUrl);
    expect($kioskUrl)->toBe(route('frame.kiosk.show', ['token' => $token]))
        ->and($frame->token_hash)->toBe(Frame::hashToken($token))
        ->and($frame->token_hash)->not->toBe($token);

    $this->get(route('frame.edit', ['frame' => $frame]))
        ->assertInertia(fn (Assert $page) => $page->where('kioskUrl', null));

    auth()->logout();
    $this->get($kioskUrl)->assertOk();
});

it('stores an empty search as null', function () {
    $this->post(route('frame.store'), frameFormData(['search_query' => '  ']));

    expect(Frame::query()->sole()->search_query)->toBeNull();
});

it('validates the frame', function (array $data, string $error) {
    $this->post(route('frame.store'), frameFormData($data))->assertSessionHasErrors($error);

    expect(Frame::query()->count())->toBe(0);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'long name' => [['name' => str_repeat('a', 256)], 'name'],
    'long search' => [['search_query' => str_repeat('a', 256)], 'search_query'],
    'interval too short' => [['interval_seconds' => 9], 'interval_seconds'],
    'interval too long' => [['interval_seconds' => 86_401], 'interval_seconds'],
    'interval not a number' => [['interval_seconds' => 'soon'], 'interval_seconds'],
    'active not boolean' => [['is_active' => 'maybe'], 'is_active'],
]);

it('edits and updates a frame without exposing the token', function () {
    $frame = Frame::factory()->create();
    $tokenHash = $frame->token_hash;

    $this->get(route('frame.edit', ['frame' => $frame]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Edit', false)
            ->where('frame.id', $frame->id)
            ->missing('frame.token_hash')
            ->where('kioskUrl', null));

    $this->put(route('frame.update', ['frame' => $frame]), frameFormData(['name' => 'Hallway', 'is_active' => false]))
        ->assertRedirect(route('frame.edit', ['frame' => $frame]));

    $frame->refresh();
    expect($frame->name)->toBe('Hallway')
        ->and($frame->is_active)->toBeFalse()
        ->and($frame->token_hash)->toBe($tokenHash);
});

it('regenerates the token, invalidating the old kiosk URL', function () {
    $oldToken = Frame::generateToken();
    $frame = Frame::factory()->withToken($oldToken)->create();

    $this->post(route('frame.token.regenerate', ['frame' => $frame]))
        ->assertRedirect(route('frame.edit', ['frame' => $frame]))
        ->assertSessionHas(FrameController::TOKEN_FLASH_KEY);

    $newToken = session(FrameController::TOKEN_FLASH_KEY);
    expect($frame->refresh()->token_hash)->toBe(Frame::hashToken($newToken))
        ->and($newToken)->not->toBe($oldToken)
        ->and($frame->isOwnedBy($this->user->id))->toBeTrue();

    auth()->logout();
    $this->get(route('frame.kiosk.show', ['token' => $oldToken]))->assertNotFound();
    $this->get(route('frame.kiosk.show', ['token' => $newToken]))->assertOk();
});

it('queues a fetch now', function () {
    Queue::fake();
    $frame = Frame::factory()->create();

    $this->post(route('frame.fetch', ['frame' => $frame]))->assertRedirect(route('frame.edit', ['frame' => $frame]));

    Queue::assertPushed(FetchFrameArtworks::class, fn (FetchFrameArtworks $job) => $job->frame->is($frame));
});

it('deletes a frame with its artworks and files', function () {
    Storage::fake(config('frame.disk'));
    $frame = Frame::factory()->create();
    FrameArtwork::factory()->for($frame)->create();
    Storage::disk(config('frame.disk'))->put("frame/{$frame->id}/1.jpg", 'x');

    $this->delete(route('frame.destroy', ['frame' => $frame]))->assertRedirect(route('frame.index'));

    expect(Frame::query()->count())->toBe(0)->and(FrameArtwork::query()->count())->toBe(0);
    Storage::disk(config('frame.disk'))->assertMissing("frame/{$frame->id}");
});

it('hides other users frames', function (string $method, string $route) {
    Queue::fake();
    $this->actingAs(User::factory()->create());
    $theirs = Frame::factory()->create();
    $this->actingAs($this->user);

    $this->{$method}(route($route, ['frame' => $theirs]), frameFormData())->assertNotFound();

    expect($theirs->fresh())->not->toBeNull();
    Queue::assertNothingPushed();
})->with([
    'edit' => ['get', 'frame.edit'],
    'update' => ['put', 'frame.update'],
    'destroy' => ['delete', 'frame.destroy'],
    'regenerate token' => ['post', 'frame.token.regenerate'],
    'fetch' => ['post', 'frame.fetch'],
]);
