<?php

use Coleus\Notes\Commands\PurgeTrashCommand;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Models\Tag;
use Coleus\Users\Models\Scopes\UserScope;
use Coleus\Users\Models\User;
use Illuminate\Console\Scheduling\Schedule;

$allNotes = fn () => Note::query()->withoutGlobalScope(UserScope::class)->withTrashed();

it('purges notes trashed more than 30 days ago for every user', function () use ($allNotes) {
    $this->actingAs(User::factory()->create());
    $old = Note::factory()->create(['deleted_at' => now()->subDays(31)]);
    $recent = Note::factory()->create(['deleted_at' => now()->subDays(29)]);
    $live = Note::factory()->create();
    $this->actingAs(User::factory()->create());
    $otherOld = Note::factory()->create(['deleted_at' => now()->subDays(45)]);
    auth()->logout();

    $this->artisan('notes:purge-trash')
        ->expectsOutputToContain('Deleted 2 note(s)')
        ->assertSuccessful();

    expect($allNotes()->pluck('id')->sort()->values()->all())->toBe(collect([$recent->id, $live->id])->sort()->values()->all());
});

it('honours --days', function () use ($allNotes) {
    $this->actingAs(User::factory()->create());
    Note::factory()->create(['deleted_at' => now()->subDays(8)]);
    auth()->logout();

    $this->artisan('notes:purge-trash', ['--days' => 7])->assertSuccessful();

    expect($allNotes()->count())->toBe(0);
});

it('reads the default from config', function () use ($allNotes) {
    config(['notes.trash_retention_days' => 60]);
    $this->actingAs(User::factory()->create());
    Note::factory()->create(['deleted_at' => now()->subDays(45)]);
    auth()->logout();

    $this->artisan('notes:purge-trash')->assertSuccessful();

    expect($allNotes()->count())->toBe(1);
});

it('rejects an invalid --days', function (string $days) {
    $this->artisan('notes:purge-trash', ['--days' => $days])->assertFailed();
})->with(['0', '-3', 'soon']);

it('removes tags left unused but keeps tags other notes still use', function () {
    $this->actingAs(User::factory()->create());
    Note::factory()->create(['body' => '#shared']);
    $old = Note::factory()->create(['body' => '#shared #only-old']);
    $old->delete();
    $old->forceFill(['deleted_at' => now()->subDays(40)])->saveQuietly();
    auth()->logout();

    $this->artisan('notes:purge-trash')->assertSuccessful();

    expect(Tag::query()->withoutGlobalScope(UserScope::class)->pluck('name')->all())->toBe(['shared']);
});

it('is scheduled daily', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains((string) $event->command, 'notes:purge-trash'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 0 * * *')
        ->and($event->withoutOverlapping)->toBeTrue();
});

it('is registered as an artisan command', function () {
    expect(array_keys(Artisan::all()))->toContain('notes:purge-trash')
        ->and(Artisan::all()['notes:purge-trash'])->toBeInstanceOf(PurgeTrashCommand::class);
});
