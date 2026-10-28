<?php

namespace Coleus\Notes\Search;

use Coleus\Notes\Services\NoteService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Full-text search over note titles and bodies. A search box query is
 * parsed by SearchQuery (operators and "phrases"); the plain words land here.
 *
 * On MariaDB/MySQL words go through the FULLTEXT(title, body) index in
 * boolean mode as required prefix terms ("+word*"). InnoDB skips words
 * shorter than innodb_ft_min_token_size and its default stopwords, and
 * splits words on punctuation, so such words (and every word on other
 * databases, like SQLite) fall back to LIKE on title and body.
 * All words must match.
 */
class NoteSearch
{
    public const int MAX_WORDS = 10;

    /**
     * InnoDB's default stopword list (INFORMATION_SCHEMA.INNODB_FT_DEFAULT_STOPWORD).
     */
    private const array INNODB_STOPWORDS = [
        'a', 'about', 'an', 'are', 'as', 'at', 'be', 'by', 'com', 'de', 'en', 'for', 'from', 'how', 'i', 'in', 'is', 'it',
        'la', 'of', 'on', 'or', 'that', 'the', 'this', 'to', 'was', 'what', 'when', 'where', 'who', 'will', 'with', 'und', 'www',
    ];

    private static ?int $minTokenSize = null;

    /**
     * @param  Builder<\Coleus\Notes\Models\Note>  $query
     */
    public static function apply(Builder $query, string $terms): void
    {
        SearchQuery::parse($terms)->apply($query);
    }

    /**
     * @param  Builder<\Coleus\Notes\Models\Note>  $query
     * @param  list<string>  $words  from words()
     */
    public static function applyWords(Builder $query, array $words): void
    {
        if ($words === []) {
            return;
        }

        $useFullText = self::supportsFullText($query);
        $fullText = [];

        foreach ($words as $word) {
            if ($useFullText && self::indexable($query, $word)) {
                $fullText[] = '+'.$word.'*';

                continue;
            }

            $pattern = '%'.NoteService::escapeLike($word).'%';
            $query->where(fn (Builder $either) => $either
                ->whereRaw("lower(title) like ? escape '!'", [$pattern])
                ->orWhereRaw("lower(body) like ? escape '!'", [$pattern]));
        }

        if ($fullText !== []) {
            $query->whereFullText(['title', 'body'], implode(' ', $fullText), ['mode' => 'boolean']);
        }
    }

    /**
     * The search words, lowercased, without FULLTEXT boolean operators or quotes.
     *
     * @return list<string>
     */
    public static function words(string $terms): array
    {
        $cleaned = (string) preg_replace('/[+\-<>()~*"@]+/u', ' ', mb_strtolower($terms));
        $words = preg_split('/\s+/u', trim($cleaned), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_slice(array_values(array_unique($words)), 0, self::MAX_WORDS);
    }

    /**
     * @param  Builder<\Coleus\Notes\Models\Note>  $query
     */
    private static function supportsFullText(Builder $query): bool
    {
        return in_array($query->getConnection()->getDriverName(), ['mariadb', 'mysql'], true);
    }

    /**
     * Whether InnoDB indexes the word as one token we can prefix-match.
     *
     * @param  Builder<\Coleus\Notes\Models\Note>  $query
     */
    private static function indexable(Builder $query, string $word): bool
    {
        return preg_match('/^[\p{L}\p{N}]+$/u', $word) === 1
            && mb_strlen($word) >= self::minTokenSize($query)
            && ! in_array($word, self::INNODB_STOPWORDS, true);
    }

    /**
     * @param  Builder<\Coleus\Notes\Models\Note>  $query
     */
    private static function minTokenSize(Builder $query): int
    {
        if (self::$minTokenSize === null) {
            $row = $query->getConnection()->selectOne("show variables like 'innodb_ft_min_token_size'");
            self::$minTokenSize = max(1, (int) ($row->Value ?? 3));
        }

        return self::$minTokenSize;
    }
}
