<?php

namespace Coleus\Notes\Markdown\WikiLinks;

use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;

class WikiLinkParser implements InlineParserInterface
{
    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::regex(WikiLinks::PATTERN_BODY);
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        ['title' => $title, 'heading' => $heading, 'label' => $label] = WikiLinks::split($inlineContext->getSubMatches()[0] ?? '');

        if ($title === '' && $heading === null) {
            return false;
        }

        $inlineContext->getCursor()->advanceBy($inlineContext->getFullMatchLength());
        $inlineContext->getContainer()->appendChild(new WikiLink($title, $label, $heading));

        return true;
    }
}
