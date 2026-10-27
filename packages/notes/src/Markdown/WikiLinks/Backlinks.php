<?php

namespace Coleus\Notes\Markdown\WikiLinks;

use Coleus\Notes\Models\Note;
use Coleus\Notes\Services\NoteService;
use Illuminate\Database\Eloquent\Collection;

/**
 * The notes whose [[wiki links]] point at a given note.
 *
 * Uses the same rules as WikiLinkResolver so a backlink shows up exactly
 * where the forward link goes: matching is by title, case-insensitive,
 * within the user's live notes; when several notes share the title the
 * link goes to the most recently updated one other than the linking note.
 */
class Backlinks
{
    public const int LIMIT = 50;

    /**
     * @return Collection<int, Note>
     */
    public static function for(Note $note): Collection
    {
        $title = mb_strtolower($note->title);

        if ($title === '' || $note->trashed()) {
            return new Collection;
        }

        $sameTitle = Note::query()
            ->whereRaw('lower(title) = ?', [$title])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->pluck('id');

        return Note::query()
            ->whereKeyNot($note->id)
            ->whereRaw("lower(body) like ? escape '!'", ['%[[%'.NoteService::escapeLike($title).'%]]%'])
            ->orderByDesc('updated_at')
            ->limit(self::LIMIT * 2)
            ->get()
            ->filter(fn (Note $candidate) => self::linksTo($candidate, $title)
                && $sameTitle->first(fn (int $id) => $id !== $candidate->id) === $note->id)
            ->take(self::LIMIT)
            ->values();
    }

    private static function linksTo(Note $candidate, string $title): bool
    {
        return in_array($title, array_map(mb_strtolower(...), WikiLinks::titles($candidate->body)), true);
    }
}
