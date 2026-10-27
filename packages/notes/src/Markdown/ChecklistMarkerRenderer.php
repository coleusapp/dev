<?php

namespace Coleus\Notes\Markdown;

use League\CommonMark\Extension\TaskList\TaskListItemMarker;
use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

/**
 * Renders "- [ ]" markers as clickable checkboxes that know which source line
 * they came from, so a click can toggle exactly that line of the Markdown.
 */
class ChecklistMarkerRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        TaskListItemMarker::assertInstanceOf($node);

        $checkbox = new HtmlElement('input', ['type' => 'checkbox'], '', true);

        if ($node->isChecked()) {
            $checkbox->setAttribute('checked', '');
        }

        $line = self::sourceLine($node);

        if ($line !== null) {
            $checkbox->setAttribute('data-task-line', (string) $line);
        } else {
            $checkbox->setAttribute('disabled', '');
        }

        return $checkbox;
    }

    /**
     * The marker is an inline node; its line is the start line of the block holding it.
     */
    public static function sourceLine(TaskListItemMarker $marker): ?int
    {
        $parent = $marker->parent();

        return $parent instanceof AbstractBlock ? $parent->getStartLine() : null;
    }
}
