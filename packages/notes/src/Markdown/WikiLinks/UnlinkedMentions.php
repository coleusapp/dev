<?php

namespace Coleus\Notes\Markdown\WikiLinks;

use Coleus\Notes\Markdown\FrontMatter;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Search\Highlighter;
use Coleus\Notes\Services\NoteService;
use Coleus\Notes\Support\NoteText;
use Illuminate\Support\Collection;

/**
 * Other notes that mention a note's title (or an alias) as plain text,
 * not as a [[link]], and turning those mentions into links (O2.8).
 *
 * A mention is the whole name as a word, in any case, in prose: not inside
 * [[links]], [text](links), bare URLs, `code`, fenced code, #tags or front
 * matter. Linking writes [[the text as written]], which resolves to the
 * note because links match names in any case.
 */
class UnlinkedMentions
{
    public const int LIMIT = 50;

    public const int CONTEXT_LINES = 3;

    /**
     * Shorter names would match too much ordinary text.
     */
    public const int MIN_NAME_LENGTH = 2;

    /**
     * @return Collection<int, array{id: int, title: string, count: int, context: list<list<array{text: string, match: bool}>>}>
     */
    public static function for(Note $note): Collection
    {
        $names = self::names($note);

        if ($names === [] || $note->trashed()) {
            return collect();
        }

        return Note::query()
            ->whereKeyNot($note->id)
            ->where(function ($query) use ($names) {
                foreach ($names as $name) {
                    $query->orWhereRaw("lower(body) like ? escape '!'", ['%'.NoteService::escapeLike($name).'%']);
                }
            })
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (Note $candidate) => ['note' => $candidate, 'found' => self::scan($candidate->body, $names)])
            ->filter(fn (array $each) => $each['found']['count'] > 0)
            ->take(self::LIMIT)
            ->map(fn (array $each) => [
                'id' => $each['note']->id,
                'title' => $each['note']->title,
                'count' => $each['found']['count'],
                'context' => $each['found']['context'],
            ])
            ->values();
    }

    /**
     * Turns every mention of $note in $source into a link; returns how many.
     */
    public static function link(Note $source, Note $note): int
    {
        $names = self::names($note);
        $count = 0;

        if ($names === []) {
            return 0;
        }

        [$frontMatter, $text] = FrontMatter::split($source->body);
        $pattern = self::pattern($names);

        $linkMentions = function (array $match) use (&$count): string {
            if (! isset($match['name']) || $match['name'] === '') {
                return $match[0];
            }

            $count++;

            return '[['.$match['name'].']]';
        };

        $body = $frontMatter.self::eachProseLine($text, fn (string $line): string => (string) preg_replace_callback($pattern, $linkMentions, $line));

        if ($count > 0) {
            app(NoteService::class)->update($source, ['body' => $body]);
        }

        return $count;
    }

    /**
     * @param  list<string>  $names
     * @return array{count: int, context: list<list<array{text: string, match: bool}>>}
     */
    private static function scan(string $body, array $names): array
    {
        $pattern = self::pattern($names);
        $count = 0;
        $context = [];

        self::eachProseLine(FrontMatter::split($body)[1], function (string $line) use ($pattern, &$count, &$context): string {
            preg_match_all($pattern, $line, $matches);
            // As plainLine shows them (runs of spaces collapsed), for highlighting.
            $found = array_values(array_map(
                fn (string $name) => (string) preg_replace('/\s+/u', ' ', $name),
                array_filter($matches['name'] ?? [], fn (string $name) => $name !== ''),
            ));

            if ($found !== []) {
                $count += count($found);

                if (count($context) < self::CONTEXT_LINES) {
                    $text = NoteText::plainLine($line);
                    $context[] = Highlighter::parts(NoteText::clipAround($text, Highlighter::firstMatch($text, $found)), $found);
                }
            }

            return $line;
        });

        return ['count' => $count, 'context' => $context];
    }

    /**
     * Runs $change over each line outside fenced code, keeping line endings.
     *
     * @param  callable(string): string  $change
     */
    private static function eachProseLine(string $text, callable $change): string
    {
        $lines = preg_split('/(\R)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$text];
        $fence = null;

        foreach ($lines as $index => $line) {
            if ($index % 2 === 1) {
                continue;
            }

            if (preg_match('/^ {0,3}(`{3,}|~{3,})/', $line, $marker) === 1) {
                $fence = $fence === null ? $marker[1] : ($marker[1][0] === $fence[0] && strlen($marker[1]) >= strlen($fence) ? null : $fence);

                continue;
            }

            if ($fence === null) {
                $lines[$index] = $change($line);
            }
        }

        return implode('', $lines);
    }

    /**
     * Matches what to leave alone (code spans, links, URLs) or a mention in
     * the "name" group. Longer names first, so "Trip to Lisbon" wins over "Trip".
     *
     * @param  list<string>  $names
     */
    private static function pattern(array $names): string
    {
        usort($names, fn (string $a, string $b) => mb_strlen($b) <=> mb_strlen($a));
        $alternatives = implode('|', array_map(fn (string $name) => implode('\\s+', array_map(fn (string $word) => preg_quote($word, '/'), explode(' ', $name))), $names));

        return '/(`+).*?\\1|!?\\[\\[[^\\[\\]\\n]*\\]\\]|!?\\[[^\\]\\n]*\\]\\([^)\\n]*\\)|<[^>\\n]+>|\\bhttps?:\\/\\/\\S+'
            .'|(?<![\\p{L}\\p{N}_#\\/])(?<name>'.$alternatives.')(?![\\p{L}\\p{N}_])/iu';
    }

    /**
     * The note's title and aliases, lowercased, long enough to look for.
     *
     * @return list<string>
     */
    private static function names(Note $note): array
    {
        return array_values(array_filter(
            array_map(fn (string $name) => WikiLinks::normalizeTitle($name), Backlinks::names($note)),
            fn (string $name) => mb_strlen($name) >= self::MIN_NAME_LENGTH && preg_match('/[\[\]|#]/', $name) !== 1,
        ));
    }
}
