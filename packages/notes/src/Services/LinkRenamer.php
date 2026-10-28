<?php

namespace Coleus\Notes\Services;

use Coleus\Notes\Markdown\WikiLinks\WikiLinkResolver;
use Coleus\Notes\Markdown\WikiLinks\WikiLinks;
use Coleus\Notes\Models\Note;
use Illuminate\Database\Eloquent\Collection;

/**
 * After a note's title changes, links written with its old title lead
 * nowhere (or to a new "create" prompt). This finds those links in the
 * user's other live notes and points them at the new title (O2.6).
 *
 * A link only counts when the old title no longer resolves to any note in
 * that note's eyes (WikiLinkResolver): if the old title is still one of the
 * note's aliases, or another note now has that title, the link is fine.
 */
class LinkRenamer
{
    /**
     * Other notes with links to $oldTitle that now lead nowhere.
     *
     * @return Collection<int, Note>
     */
    public function staleLinks(Note $note, string $oldTitle): Collection
    {
        $key = mb_strtolower(WikiLinks::normalizeTitle($oldTitle));

        if ($key === '' || $key === mb_strtolower($note->title)) {
            return new Collection;
        }

        $query = Note::query()->whereKeyNot($note->id)->where('body', 'like', '%[[%');

        // Each word of the title, since a link may space it differently ([[Old  title]]).
        foreach (explode(' ', $key) as $word) {
            $query->whereRaw("lower(body) like ? escape '!'", ['%'.NoteService::escapeLike($word).'%']);
        }

        return $query
            ->get()
            ->filter(fn (Note $candidate) => in_array($key, array_map('mb_strtolower', WikiLinks::titles($candidate->body)), true)
                && WikiLinkResolver::forBody($candidate->body, $candidate->id)->resolve($key) === null)
            ->values();
    }

    /**
     * Rewrites those links to the note's current title; returns how many
     * notes changed. Each save is a normal edit (version history, tags).
     */
    public function relink(Note $note, string $oldTitle): int
    {
        $changed = 0;

        foreach ($this->staleLinks($note, $oldTitle) as $candidate) {
            $body = WikiLinks::renameTitle($candidate->body, $oldTitle, $note->title);

            if ($body !== $candidate->body) {
                app(NoteService::class)->update($candidate, ['body' => $body]);
                $changed++;
            }
        }

        return $changed;
    }
}
