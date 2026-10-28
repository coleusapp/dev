<?php

namespace Coleus\Notes\Markdown\Math;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Node\Node;
use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;

/**
 * Math (O5.4): $inline$ and $$block$$ TeX. The server only marks it up
 * (data-tex, with the source as fallback text); KaTeX renders it in the
 * browser. Inline math follows Pandoc's rule so prices don't turn into
 * math: no space just inside the dollars, and no digit right after the
 * closing one ("$5 and $10" stays text).
 */
class MathExtension implements ExtensionInterface, InlineParserInterface, NodeRendererInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addBlockStartParser(MathBlockParser::blockStartParser(), 70);
        $environment->addInlineParser($this, 150);
        $environment->addRenderer(MathInline::class, $this);
        $environment->addRenderer(MathBlock::class, $this);
    }

    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::regex('\$(?![\s$])((?:\\\\.|[^$\\\\\n])+?)(?<![\s\\\\])\$(?!\d)');
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        $cursor = $inlineContext->getCursor();

        if ($cursor->peek(-1) === '$') {
            return false;
        }

        $cursor->advanceBy($inlineContext->getFullMatchLength());
        $inlineContext->getContainer()->appendChild(new MathInline($inlineContext->getSubMatches()[0] ?? ''));

        return true;
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        if ($node instanceof MathBlock) {
            $tex = implode("\n", $node->lines);

            return new HtmlElement('div', ['class' => 'math math-block', 'data-tex' => $tex], Xml::escape('$$'.$tex.'$$'));
        }

        MathInline::assertInstanceOf($node);

        return new HtmlElement('span', ['class' => 'math math-inline', 'data-tex' => $node->tex], Xml::escape('$'.$node->tex.'$'));
    }
}
