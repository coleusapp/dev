<?php

namespace Coleus\Notes\Markdown\Bases;

use Coleus\Notes\Bases\BaseDefinition;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

/**
 * Bases embedded in notes (O11.3, Obsidian's ![[Books.base]]): a line of
 * its own naming a base by name (any case) with ".base" after it, and
 * optionally |table or |cards to override the base's own view. Rendered
 * by BaseEmbedRenderer; the base is looked up then, so parsing needs no
 * database.
 */
class BaseEmbedExtension implements ExtensionInterface
{
    public const string LINE_PATTERN = '/^!\[\[([^\[\]\n|#]{1,100})\.base(?:\|\s*(\w+)\s*)?\]\][ \t]*$/iu';

    public function register(EnvironmentBuilderInterface $environment): void
    {
        // Before note embeds (80), which would take it for a note called "….base".
        $environment->addBlockStartParser(self::blockStartParser(), 90);
        $environment->addRenderer(BaseEmbed::class, new BaseEmbedRenderer);
    }

    public static function isBaseName(string $name): bool
    {
        return preg_match('/\.base$/i', trim($name)) === 1;
    }

    private static function blockStartParser(): BlockStartParserInterface
    {
        return new class implements BlockStartParserInterface
        {
            public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
            {
                if ($cursor->isIndented() || preg_match(BaseEmbedExtension::LINE_PATTERN, $cursor->getRemainder(), $match) !== 1) {
                    return BlockStart::none();
                }

                $view = mb_strtolower($match[2] ?? '');
                $cursor->advanceToEnd();
                $embed = new BaseEmbed(trim($match[1]), in_array($view, BaseDefinition::VIEWS, true) ? $view : null);

                return BlockStart::of(new class($embed) extends AbstractBlockContinueParser
                {
                    public function __construct(private readonly BaseEmbed $embed)
                    {
                        //
                    }

                    public function getBlock(): BaseEmbed
                    {
                        return $this->embed;
                    }

                    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue
                    {
                        return BlockContinue::none();
                    }
                })->at($cursor);
            }
        };
    }
}
