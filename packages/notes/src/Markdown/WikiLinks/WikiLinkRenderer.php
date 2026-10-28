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
 * A heading travels as ?heading= (the note scrolls to it on open, like a
 * heading bookmark) and data-heading; [[#Heading]] scrolls the same note.
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

        $text = Xml::escape(WikiLinks::text(['title' => $node->title, 'heading' => $node->heading, 'label' => $node->label]));
        $heading = $node->heading === null ? [] : ['data-heading' => $node->heading];

        if ($node->title === '') {
            return new HtmlElement('a', ['href' => '#', 'class' => 'wiki-link', ...$heading], $text);
        }

        $noteId = $this->resolver?->resolve($node->title);

        if ($noteId !== null) {
            return new HtmlElement('a', [
                'href' => route('notes.show', ['note' => $noteId, ...($node->heading === null ? [] : ['heading' => $node->heading])], false),
                'class' => 'wiki-link',
                'data-note-id' => (string) $noteId,
                ...$heading,
            ], $text);
        }

        return new HtmlElement('a', [
            'href' => '#',
            'class' => 'wiki-link wiki-link-missing',
            'data-wiki-title' => $node->title,
            'title' => 'Create "'.$node->title.'"',
        ], $text);
    }
}
