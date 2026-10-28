<?php

namespace Coleus\Notes\Services;

use Coleus\Notes\Markdown\FrontMatter;
use Coleus\Notes\Markdown\WikiLinks\WikiLinks;
use Coleus\Notes\Models\Note;

/**
 * Keeps a note's aliases in step with its "aliases" property (a list, or
 * one comma-separated string), like Obsidian.
 */
class NoteAliases
{
    /**
     * @return list<string>
     */
    public static function fromBody(string $body): array
    {
        $value = (FrontMatter::parse($body) ?? [])['aliases'] ?? null;
        $aliases = is_array($value) ? $value : (is_string($value) ? explode(',', $value) : []);

        return collect($aliases)
            ->map(fn (mixed $alias) => WikiLinks::normalizeTitle((string) $alias))
            ->filter(fn (string $alias) => $alias !== '' && mb_strlen($alias) <= 255 && preg_match('/[\[\]]/', $alias) !== 1)
            ->unique(fn (string $alias) => mb_strtolower($alias))
            ->values()
            ->all();
    }

    public static function sync(Note $note): void
    {
        $note->aliases()->delete();
        $note->aliases()->createMany(array_map(fn (string $alias) => ['alias' => $alias, 'alias_lower' => mb_strtolower($alias)], self::fromBody($note->body)));
    }
}
