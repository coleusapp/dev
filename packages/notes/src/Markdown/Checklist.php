<?php

namespace Coleus\Notes\Markdown;

use League\CommonMark\Extension\TaskList\TaskListItemMarker;

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
}
