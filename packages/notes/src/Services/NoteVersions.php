<?php

namespace Coleus\Notes\Services;

use Coleus\Notes\Models\Note;
use Coleus\Notes\Models\NoteVersion;

/**
 * Keeps a throttled history of each note's body.
 *
 * Every save that changes the body is recorded, but saves within the same
 * window (notes.versions.interval_minutes) update the latest unnamed
 * snapshot instead of adding one, so typing with autosave produces one
 * version per pause rather than one per keystroke burst. Named versions
 * are never overwritten or pruned; only the newest notes.versions.keep
 * unnamed ones are kept.
 */
class NoteVersions
{
    public static function record(Note $note): void
    {
        $latest = $note->versions()->latest('created_at')->latest('id')->first();

        if ($latest !== null && $latest->body === $note->body) {
            return;
        }

        $window = now()->subMinutes(max(0, (int) config('notes.versions.interval_minutes', 5)));

        if ($latest !== null && $latest->name === null && $latest->created_at?->greaterThan($window)) {
            $latest->update(['title' => $note->title, 'body' => $note->body]);

            return;
        }

        $note->versions()->create(['title' => $note->title, 'body' => $note->body]);

        self::prune($note);
    }

    private static function prune(Note $note): void
    {
        $keep = max(1, (int) config('notes.versions.keep', 100));

        $stale = $note->versions()
            ->whereNull('name')
            ->latest('created_at')
            ->latest('id')
            ->skip($keep)
            ->take(PHP_INT_MAX)
            ->pluck('id');

        if ($stale->isNotEmpty()) {
            NoteVersion::query()->whereKey($stale)->delete();
        }
    }
}
