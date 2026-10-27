<?php

namespace Coleus\Notes\Markdown;

use League\CommonMark\Extension\TaskList\TaskListItemMarker;
use League\CommonMark\Node\Block\AbstractBlock;

/**
 * Finds and flips "- [ ]" / "- [x]" items in a note body by source line.
 */
class Checklist
{
    private const string MARKER_PATTERN = '/^((?:[ \t]*>)*[ \t]*(?:[-*+]|\d{1,9}[.)])[ \t]+)\[([ xX])\]/';

    /**
     * The 1-based source lines that hold a real task list item, according to
     * the Markdown parser (so look-alikes inside code blocks don't count).
     *
     * @return list<int>
     */
    public static function taskLines(string $body): array
    {
        $lines = [];

        foreach (NoteMarkdown::parse($body)->iterator() as $node) {
            if ($node instanceof TaskListItemMarker && ($line = ChecklistMarkerRenderer::sourceLine($node)) !== null) {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    /**
     * Returns the body with the task item on $line checked or unchecked,
     * or null when that line isn't a task item. Line endings are preserved.
     */
    public static function toggle(string $body, int $line, bool $checked): ?string
    {
        if (! in_array($line, self::taskLines($body), true)) {
            return null;
        }

        $parts = preg_split('/(\R)/', $body, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $index = ($line - 1) * 2;

        if (! isset($parts[$index])) {
            return null;
        }

        $replaced = preg_replace(self::MARKER_PATTERN, '${1}['.($checked ? 'x' : ' ').']', $parts[$index], 1, $count);

        if ($replaced === null || $count !== 1) {
            return null;
        }

        $parts[$index] = $replaced;

        return implode('', $parts);
    }

    /**
     * Moves the task item starting on $line (with its sub-items) so it sits
     * just before the item starting on $beforeLine, or at the end of its
     * list when $beforeLine is null. Both must be items of the same list.
     * Returns null when the move isn't possible. Line endings are preserved.
     */
    public static function move(string $body, int $line, ?int $beforeLine): ?string
    {
        $items = self::itemBlocks($body);
        $item = $items[$line] ?? null;
        $target = $beforeLine === null ? null : ($items[$beforeLine] ?? null);

        if ($item === null || ($beforeLine !== null && ($target === null || $target['list'] !== $item['list']))) {
            return null;
        }

        $eol = preg_match('/\r\n/', $body) === 1 ? "\r\n" : "\n";
        $lines = preg_split('/\R/', $body) ?: [];
        $block = array_slice($lines, $item['start'] - 1, $item['end'] - $item['start'] + 1);
        $insertAt = $target === null
            ? max(array_map(fn (array $sibling) => $sibling['end'], array_filter($items, fn (array $sibling) => $sibling['list'] === $item['list'])))
            : $target['start'] - 1;

        if ($insertAt >= $item['start'] - 1 && $insertAt <= $item['end']) {
            return $body;
        }

        array_splice($lines, $item['start'] - 1, count($block));

        if ($insertAt > $item['end']) {
            $insertAt -= count($block);
        }

        array_splice($lines, $insertAt, 0, $block);

        return implode($eol, $lines);
    }

    /**
     * Task items keyed by their first line: the 1-based line range of the
     * whole item (sub-items included, trailing blank lines excluded) and an
     * id for the list it belongs to.
     *
     * @return array<int, array{start: int, end: int, list: int}>
     */
    private static function itemBlocks(string $body): array
    {
        $lines = preg_split('/\R/', $body) ?: [];
        $items = [];

        foreach (NoteMarkdown::parse($body)->iterator() as $node) {
            $listItem = $node instanceof TaskListItemMarker ? $node->parent()?->parent() : null;
            $list = $listItem?->parent();

            if (! $listItem instanceof AbstractBlock || ! $list instanceof AbstractBlock) {
                continue;
            }

            $start = $listItem->getStartLine();
            $end = $listItem->getEndLine();

            if ($start === null || $end === null) {
                continue;
            }

            while ($end > $start && trim($lines[$end - 1] ?? '') === '') {
                $end--;
            }

            $items[$start] = ['start' => $start, 'end' => $end, 'list' => spl_object_id($list)];
        }

        return $items;
    }
}
