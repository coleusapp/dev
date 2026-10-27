<?php

namespace Coleus\Notes\Markdown\WikiLinks;

use Coleus\Notes\Models\Note;
use Illuminate\Support\Facades\DB;

/**
 * Maps wiki link titles to the current user's notes, case-insensitively.
 * Titles are looked up in one query up front. When several notes share a
 * title, the most recently updated one wins. Trashed notes don't resolve,
 * and neither does the note doing the linking.
 */
class WikiLinkResolver
{
    /**
     * @param  array<string, int>  $idsByTitle  lowercased title => note id
     */
    public function __construct(private readonly array $idsByTitle)
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
            return new self([]);
        }

        $lowered = array_map(mb_strtolower(...), $titles);
        $ids = [];

        Note::query()
            ->whereIn(DB::raw('lower(title)'), $lowered)
            ->when($fromNoteId !== null, fn ($query) => $query->whereKeyNot($fromNoteId))
            ->orderBy('updated_at')
            ->orderBy('id')
            ->get(['id', 'title'])
            ->each(function (Note $note) use (&$ids) {
                $ids[mb_strtolower($note->title)] = $note->id;
            });

        return new self($ids);
    }

    public function resolve(string $title): ?int
    {
        return $this->idsByTitle[mb_strtolower($title)] ?? null;
    }
}
