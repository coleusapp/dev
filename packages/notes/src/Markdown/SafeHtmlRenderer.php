<?php

namespace Coleus\Notes\Markdown;

use League\CommonMark\Extension\CommonMark\Node\Block\HtmlBlock;
use League\CommonMark\Extension\CommonMark\Node\Inline\HtmlInline;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\Xml;

/**
 * Raw HTML in a note (O5.8): everything is escaped and shown as text,
 * except a few plain formatting tags, written without attributes:
 * <kbd>, <sup>, <sub>, <mark>, <u>, <ins>, <del>, <s>, <small>, <abbr>,
 * <br>, <details> (optionally "open") and <summary>. So nothing a note
 * holds can run script, load anything or restyle the page.
 */
class SafeHtmlRenderer implements NodeRendererInterface
{
    public const array ALLOWED = ['kbd', 'sup', 'sub', 'mark', 'u', 'ins', 'del', 's', 'small', 'abbr', 'br', 'details', 'summary'];

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        if (! $node instanceof HtmlBlock && ! $node instanceof HtmlInline) {
            return '';
        }

        $html = self::allowSafeTags(Xml::escape($node->getLiteral()));

        return $node instanceof HtmlBlock ? $html."\n" : $html;
    }

    /**
     * Turns the escaped forms of allowed tags back into tags.
     */
    public static function allowSafeTags(string $escaped): string
    {
        $tags = implode('|', self::ALLOWED);

        return (string) preg_replace_callback(
            '/&lt;(\/?)('.$tags.')(\s+open)?\s*(\/?)&gt;/i',
            function (array $match): string {
                $tag = strtolower($match[2]);
                $open = $match[3] !== '' && $tag === 'details' && $match[1] === '' ? ' open' : '';

                if ($match[3] !== '' && $open === '') {
                    return $match[0];
                }

                return $tag === 'br' ? '<br>' : '<'.$match[1].$tag.$open.'>';
            },
            $escaped,
        );
    }
}
