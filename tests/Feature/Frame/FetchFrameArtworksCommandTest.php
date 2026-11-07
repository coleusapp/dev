<?php

use Coleus\Frame\Commands\FetchFrameArtworksCommand;
use Coleus\Frame\Jobs\FetchFrameArtworks;
use Coleus\Frame\Models\Frame;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

it('queues a fetch for every active frame', function () {
    $active = Frame::factory()->count(2)->create();
    Frame::factory()->inactive()->create();

    $this->artisan('frame:fetch')->assertSuccessful();

    Queue::assertPushed(FetchFrameArtworks::class, 2);
    $active->each(fn (Frame $frame) => Queue::assertPushed(
        FetchFrameArtworks::class,
        fn (FetchFrameArtworks $job) => $job->frame->is($frame) && $job->count === 20,
    ));
});

it('queues a fetch for one frame, even an inactive one, with a custom count', function () {
    $frame = Frame::factory()->inactive()->create();
    Frame::factory()->create();

    $this->artisan('frame:fetch', ['frame' => $frame->id, '--count' => 5])->assertSuccessful();

    Queue::assertPushed(FetchFrameArtworks::class, 1);
    Queue::assertPushed(FetchFrameArtworks::class, fn (FetchFrameArtworks $job) => $job->frame->is($frame) && $job->count === 5);
});

it('fails for an unknown frame', function () {
    $this->artisan('frame:fetch', ['frame' => 999])->assertFailed();

    Queue::assertNothingPushed();
});

it('rejects a count outside 1 to 100', function (int $count) {
    Frame::factory()->create();

    $this->artisan('frame:fetch', ['--count' => $count])->assertFailed();

    Queue::assertNothingPushed();
})->with([0, 101]);

it('is scheduled every six hours without overlapping', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn (Event $event) => str_contains((string) $event->command, 'frame:fetch'));

    expect($events)->toHaveCount(1)
        ->and($events->first()->expression)->toBe('0 */6 * * *')
        ->and($events->first()->withoutOverlapping)->toBeTrue()
        ->and(app(FetchFrameArtworksCommand::class)->getName())->toBe('frame:fetch');
});
