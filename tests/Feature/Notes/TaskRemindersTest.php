<?php

use Coleus\Notes\Models\Note;
use Coleus\Notes\Notifications\TasksDueNotification;
use Coleus\Users\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    Carbon::setTestNow('2026-10-28 08:00');
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

afterEach(fn () => Carbon::setTestNow());

it('reminds of open items due soon or overdue, once each', function () {
    Note::factory()->create(['body' => "# Bills\n- [ ] pay rent @2026-10-29\n- [ ] taxes @2026-12-01\n- [x] done @2026-10-28\n- [ ] late @2026-10-01"]);
    Note::factory()->create(['body' => "# Old\n- [ ] trashed @2026-10-28"])->delete();
    auth()->logout();

    $this->artisan('notes:send-reminders')->assertSuccessful();

    Notification::assertSentTo($this->user, TasksDueNotification::class, fn (TasksDueNotification $notification) => array_column($notification->items, 'text') === ['late', 'pay rent']);

    $this->artisan('notes:send-reminders')->assertSuccessful();
    Notification::assertSentToTimes($this->user, TasksDueNotification::class, 1);
});

it('reminds again when the due date changes, and only the owner', function () {
    $note = Note::factory()->create(['body' => "# Bills\n- [ ] pay rent @2026-10-29"]);
    $other = User::factory()->create();
    auth()->logout();
    $this->artisan('notes:send-reminders');

    $this->actingAs($this->user);
    $note->update(['body' => "# Bills\n- [ ] pay rent @2026-10-28"]);
    auth()->logout();
    $this->artisan('notes:send-reminders');

    Notification::assertSentToTimes($this->user, TasksDueNotification::class, 2);
    Notification::assertNothingSentTo($other);
});

it('can be turned off', function () {
    config(['notes.reminders.enabled' => false]);
    Note::factory()->create(['body' => "# Bills\n- [ ] pay rent @2026-10-28"]);
    auth()->logout();

    $this->artisan('notes:send-reminders')->assertSuccessful();

    Notification::assertNothingSent();
});
