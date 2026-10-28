<?php

namespace Coleus\Notes\Markdown\Callouts;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;

/**
 * A callout box: a <div>, or a <details> when foldable ("+" starts open).
 */
class CalloutRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        Callout::assertInstanceOf($node);

        $foldable = $node->fold !== '';
        $attributes = ['class' => 'callout callout-'.$node->family, 'data-callout' => $node->type, ...($node->fold === '+' ? ['open' => ''] : [])];
        $title = new HtmlElement($foldable ? 'summary' : 'div', ['class' => 'callout-title'], Xml::escape($node->title));
        $body = $node->firstChild() === null ? '' : new HtmlElement('div', ['class' => 'callout-body'], $childRenderer->renderNodes($node->children()));

        return new HtmlElement($foldable ? 'details' : 'div', $attributes, [$title, $body]);
    }
}
