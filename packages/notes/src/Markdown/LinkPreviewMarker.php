<?php

namespace Coleus\Notes\Markdown;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\NodeIterator;

/**
 * A web address alone on its line becomes a link-preview card (A4.1): it
 * stays a plain link in the HTML, marked data-link-preview, and the
 * browser asks the server for the card (so rendering never waits on
 * other websites).
 */
class LinkPreviewMarker implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addEventListener(DocumentParsedEvent::class, function (DocumentParsedEvent $event) {
            foreach ($event->getDocument()->iterator(NodeIterator::FLAG_BLOCKS_ONLY) as $node) {
                $link = $node instanceof Paragraph ? $node->firstChild() : null;

                if ($link instanceof Link && $link->next() === null && preg_match('#^https?://#i', $link->getUrl()) === 1 && self::isBare($link)) {
                    $link->data->set('attributes/data-link-preview', '');
                    $link->data->append('attributes/class', 'link-preview');
                }
            }
        }, -10);
    }

    /**
     * Only the address itself (an autolink, or [url](url)), not a link
     * someone gave other words to.
     */
    private static function isBare(Link $link): bool
    {
        $text = $link->firstChild();

        return $text instanceof Text && $text->next() === null && rtrim($text->getLiteral(), '/') === rtrim(preg_replace('#^mailto:#', '', $link->getUrl()) ?? '', '/');
    }
}
