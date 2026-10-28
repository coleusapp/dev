<?php

namespace Coleus\Notes\Markdown\WikiLinks;

use Coleus\Notes\Models\Note;
use Coleus\Notes\Services\NoteService;
use Illuminate\Database\Eloquent\Collection;

/**
 * The notes whose [[wiki links]] point at a given note.
 *
 * Uses WikiLinkResolver itself, so a backlink shows up exactly where the
 * forward link goes: by title or alias, case-insensitive, within the user's
 * live notes, a title beating an alias and the most recently updated note
 * winning between equals.
 */
class Backlinks
{
    public const int LIMIT = 50;

    /**
     * @return Collection<int, Note>
     */
    public static function for(Note $note): Collection
    {
        if ($note->trashed()) {
            return new Collection;
        }

        $names = collect([$note->title, ...$note->aliases()->pluck('alias')->all()])
            ->map(fn (string $name) => mb_strtolower($name))
            ->filter(fn (string $name) => $name !== '')
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            return new Collection;
        }

        return Note::query()
            ->whereKeyNot($note->id)
            ->where(function ($query) use ($names) {
                foreach ($names as $name) {
                    $query->orWhereRaw("lower(body) like ? escape '!'", ['%[[%'.NoteService::escapeLike($name).'%]]%']);
                }
            })
            ->orderByDesc('updated_at')
            ->limit(self::LIMIT * 2)
            ->get()
            ->filter(fn (Note $candidate) => self::linksTo($candidate, $note, $names->all()))
            ->take(self::LIMIT)
            ->values();
    }

    /**
     * Whether one of the candidate's [[links]] uses a name of the note and,
     * by the resolver's rules, really leads to it.
     *
     * @param  list<string>  $names
     */
    private static function linksTo(Note $candidate, Note $note, array $names): bool
    {
        $linked = array_values(array_filter(WikiLinks::titles($candidate->body), fn (string $title) => in_array(mb_strtolower($title), $names, true)));

        if ($linked === []) {
            return false;
        }

        $resolver = WikiLinkResolver::forBody($candidate->body, $candidate->id);

        return collect($linked)->contains(fn (string $title) => $resolver->resolve($title) === $note->id);
    }
}
