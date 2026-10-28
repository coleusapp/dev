<?php

namespace Coleus\Notes\Markdown\WikiLinks;

use Coleus\Notes\Models\Note;
use Coleus\Notes\Models\NoteAlias;
use Illuminate\Support\Facades\DB;

/**
 * Maps wiki link titles to the current user's notes, case-insensitively,
 * by title or by one of the note's aliases (a title match wins).
 * Titles are looked up in one query up front. When several notes share a
 * title, the most recently updated one wins. Trashed notes don't resolve,
 * and neither does the note doing the linking.
 */
class WikiLinkResolver
{
    /**
     * @param  array<string, int>  $idsByTitle  lowercased title => note id
     */
    public function __construct(private readonly array $idsByTitle, public readonly ?int $fromNoteId = null)
    {
        //
    }

    /**
     * @param  int|null  $fromNoteId  the note being rendered; it never links to itself
     *                                (its own title can come from a "[[Title]]" first line)
     */
    public static function forBody(string $body, ?int $fromNoteId = null): self
    {
        $titles = WikiLinks::titles($body);

        if ($titles === []) {
            return new self([], $fromNoteId);
        }

        $lowered = array_map(mb_strtolower(...), $titles);
        $ids = [];

        // Aliases first, so a note whose real title matches wins over one that only has it as an alias.
        NoteAlias::query()
            ->whereIn('alias_lower', $lowered)
            ->whereHas('note', fn ($query) => $query->when($fromNoteId !== null, fn ($notes) => $notes->whereKeyNot($fromNoteId)))
            ->with('note:id,updated_at')
            ->get()
            ->sortBy(fn (NoteAlias $alias) => [$alias->note->updated_at?->getTimestamp() ?? 0, $alias->note_id])
            ->each(function (NoteAlias $alias) use (&$ids) {
                $ids[$alias->alias_lower] = $alias->note_id;
            });

        $byTitle = [];

        Note::query()
            ->whereIn(DB::raw('lower(title)'), $lowered)
            ->when($fromNoteId !== null, fn ($query) => $query->whereKeyNot($fromNoteId))
            ->orderBy('updated_at')
            ->orderBy('id')
            ->get(['id', 'title'])
            ->each(function (Note $note) use (&$byTitle) {
                $byTitle[mb_strtolower($note->title)] = $note->id;
            });

        $ids = [...$ids, ...$byTitle];

        return new self($ids, $fromNoteId);
    }

    public function resolve(string $title): ?int
    {
        return $this->idsByTitle[mb_strtolower($title)] ?? null;
    }
}
