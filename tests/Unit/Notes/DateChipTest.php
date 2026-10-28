<?php

use Coleus\Notes\Markdown\NoteMarkdown;
use Tests\TestCase;

uses(TestCase::class);

it('renders @dates as chips', function () {
    expect(NoteMarkdown::toHtml('Call on @2026-10-28.'))
        ->toBe("<p>Call on <time class=\"date-chip\" datetime=\"2026-10-28\" title=\"Wednesday, October 28, 2026\">Oct 28, 2026</time>.</p>\n");
});

it('ignores e-mails, invalid dates, code and dates glued to words', function (string $markdown) {
    expect(NoteMarkdown::toHtml($markdown))->not->toContain('date-chip');
})->with([
    'email' => ['sam@2026-10-28.test'],
    'glued' => ['x@2026-10-28'],
    'invalid day' => ['@2026-02-30'],
    'longer' => ['@2026-10-281'],
    'code' => ['`@2026-10-28`'],
]);

it('links chips to the calendar when configured', function () {
    config(['notes.calendar_url' => '/calendar?date={date}']);

    expect(NoteMarkdown::toHtml('@2026-10-28'))->toContain('<a href="/calendar?date=2026-10-28" class="date-chip-link"><time');
});
