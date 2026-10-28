<?php

namespace Coleus\Notes\Markdown\WikiLinks;

use Coleus\Notes\Markdown\FrontMatter;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Search\Highlighter;
use Coleus\Notes\Services\NoteService;
use Coleus\Notes\Support\NoteText;
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
     * Linking lines shown per backlink.
     */
    public const int CONTEXT_LINES = 3;

    /**
     * @return Collection<int, Note>
     */
    public static function for(Note $note): Collection
    {
        if ($note->trashed()) {
            return new Collection;
        }

        $names = collect(self::names($note));

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
     * The lines of $source that link to a note by one of its $names (see
     * names()), as plain text around the link with the link's shown text
     * marked. At most $limit; code and front matter are skipped.
     *
     * @param  list<string>  $names
     * @return list<list<array{text: string, match: bool}>>
     */
    public static function context(Note $source, array $names, int $limit = self::CONTEXT_LINES): array
    {
        $lines = [];
        $fence = null;

        foreach (preg_split('/\R/', FrontMatter::blankOut($source->body)) ?: [] as $line) {
            if (preg_match('/^ {0,3}(`{3,}|~{3,})/', $line, $marker) === 1) {
                $fence = $fence === null ? $marker[1] : ($marker[1][0] === $fence[0] && strlen($marker[1]) >= strlen($fence) ? null : $fence);

                continue;
            }

            if ($fence !== null || ! str_contains($line, '[[')) {
                continue;
            }

            $shown = [];
            $outsideCode = (string) preg_replace('/(`+).*?\1/u', '', $line);

            preg_match_all('/'.WikiLinks::PATTERN_BODY.'/u', $outsideCode, $matches);

            foreach ($matches[1] as $inner) {
                $link = WikiLinks::split($inner);

                if (in_array(mb_strtolower($link['title']), $names, true)) {
                    $shown[] = WikiLinks::text($link);
                }
            }

            if ($shown === []) {
                continue;
            }

            $text = NoteText::plainLine($line);
            $lines[] = Highlighter::parts(NoteText::clipAround($text, Highlighter::firstMatch($text, $shown)), $shown);

            if (count($lines) === $limit) {
                break;
            }
        }

        return $lines;
    }

    /**
     * A note's title and aliases, lowercased: the names links find it by.
     *
     * @return list<string>
     */
    public static function names(Note $note): array
    {
        return collect([$note->title, ...$note->aliases()->pluck('alias')->all()])
            ->map(fn (string $name) => mb_strtolower($name))
            ->filter(fn (string $name) => $name !== '')
            ->unique()
            ->values()
            ->all();
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
