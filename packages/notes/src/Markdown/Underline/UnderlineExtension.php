<?php

namespace Coleus\Notes\Markdown\Underline;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

class UnderlineExtension implements ExtensionInterface, NodeRendererInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addDelimiterProcessor(new UnderlineDelimiterProcessor);
        $environment->addRenderer(Underline::class, $this);
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        Underline::assertInstanceOf($node);

        return new HtmlElement('u', [], $childRenderer->renderNodes($node->children()));
    }
}
