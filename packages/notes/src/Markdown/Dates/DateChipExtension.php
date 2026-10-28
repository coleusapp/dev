<?php

namespace Coleus\Notes\Markdown\Dates;

use Carbon\CarbonImmutable;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Node\Node;
use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

/**
 * "@YYYY-MM-DD" (a real date, starting a word, so e-mail addresses and
 * "x@2026-…" don't count) renders as a <time> chip (B5.4). When
 * config('notes.calendar_url') is set (e.g. "/calendar?date={date}") the
 * chip links there; otherwise it's just a chip.
 */
class DateChipExtension implements ExtensionInterface, InlineParserInterface, NodeRendererInterface
{
    public const string PATTERN = '@(\d{4}-\d{2}-\d{2})(?![\w-])';

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addInlineParser($this, 50);
        $environment->addRenderer(DateChip::class, $this);
    }

    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::regex(self::PATTERN);
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        $cursor = $inlineContext->getCursor();
        $before = $cursor->peek(-1);

        if ($before !== null && preg_match('/[\p{L}\p{N}_.@]/u', $before) === 1) {
            return false;
        }

        $value = $inlineContext->getSubMatches()[0] ?? '';
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);

        if ($date === null || $date->format('Y-m-d') !== $value) {
            return false;
        }

        $cursor->advanceBy($inlineContext->getFullMatchLength());
        $inlineContext->getContainer()->appendChild(new DateChip($date));

        return true;
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        DateChip::assertInstanceOf($node);

        $iso = $node->date->format('Y-m-d');
        $chip = new HtmlElement('time', ['class' => 'date-chip', 'datetime' => $iso, 'title' => $node->date->format('l, F j, Y')], $node->date->format('M j, Y'));
        $url = config('notes.calendar_url');

        return is_string($url) && $url !== ''
            ? new HtmlElement('a', ['href' => str_replace('{date}', $iso, $url), 'class' => 'date-chip-link'], $chip)
            : $chip;
    }
}
