<?php

namespace Coleus\Notes\Markdown\WikiLinks;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;

/**
 * Renders a wiki link to the note it names, or, when no note has that
 * title, to a "missing" link the page turns into a create-note prompt.
 */
class WikiLinkRenderer implements NodeRendererInterface
{
    public function __construct(private readonly ?WikiLinkResolver $resolver)
    {
        //
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        WikiLink::assertInstanceOf($node);

        $noteId = $this->resolver?->resolve($node->title);

        if ($noteId !== null) {
            return new HtmlElement('a', [
                'href' => route('notes.show', ['note' => $noteId], false),
                'class' => 'wiki-link',
                'data-note-id' => (string) $noteId,
            ], Xml::escape($node->title));
        }

        return new HtmlElement('a', [
            'href' => '#',
            'class' => 'wiki-link wiki-link-missing',
            'data-wiki-title' => $node->title,
            'title' => 'Create "'.$node->title.'"',
        ], Xml::escape($node->title));
    }
}
