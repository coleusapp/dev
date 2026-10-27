<?php

namespace Coleus\Notes\Services;

use Coleus\Notes\Markdown\Hashtags;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Models\Tag;
use Illuminate\Database\Eloquent\Builder;

/**
 * Builds the sidebar's tag tree for the current user.
 *
 * Each node counts the distinct notes tagged with it or any tag below it, so
 * a note tagged #work/meetings counts once under "work" and once under
 * "work/meetings". Parents that exist only as a prefix ("work" when only
 * #work/meetings is used) still appear as nodes.
 */
class TagTree
{
    /**
     * @param  Builder<Note>  $visibleNotes  the notes the counts are about
     * @return list<array{name: string, path: string, count: int, children: list<mixed>}>
     */
    public static function build(Builder $visibleNotes): array
    {
        $notesByPath = [];

        $tags = Tag::query()
            ->with(['notes' => fn ($query) => $query->whereIn($query->getModel()->getQualifiedKeyName(), (clone $visibleNotes)->select('id'))->select('id')])
            ->get();

        foreach ($tags as $tag) {
            foreach ($tag->notes as $note) {
                foreach ([...Hashtags::ancestors($tag->name), $tag->name] as $path) {
                    $notesByPath[$path][$note->id] = true;
                }
            }
        }

        ksort($notesByPath, SORT_STRING);

        return self::children($notesByPath, null);
    }

    /**
     * @param  array<string, array<int, true>>  $notesByPath
     * @return list<array{name: string, path: string, count: int, children: list<mixed>}>
     */
    private static function children(array $notesByPath, ?string $parent): array
    {
        $nodes = [];

        foreach ($notesByPath as $path => $noteIds) {
            $parentPath = str_contains($path, '/') ? substr($path, 0, (int) strrpos($path, '/')) : null;

            if ($parentPath !== $parent) {
                continue;
            }

            $nodes[] = [
                'name' => $parentPath === null ? $path : substr($path, strlen($parentPath) + 1),
                'path' => $path,
                'count' => count($noteIds),
                'children' => self::children($notesByPath, $path),
            ];
        }

        return $nodes;
    }
}
