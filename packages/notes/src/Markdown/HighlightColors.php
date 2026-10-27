<?php

namespace Coleus\Notes\Markdown;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Extension\Highlight\Mark;
use League\CommonMark\Node\Inline\Text;

/**
 * Coloured highlights: "==text=={pink}". The colour suffix (one of COLORS)
 * right after a ==highlight== becomes a mark-<colour> class on its <mark>
 * and is removed from the text. Unknown colours are left as typed.
 */
class HighlightColors implements ExtensionInterface
{
    public const array COLORS = ['pink', 'purple', 'orange', 'mint', 'blue'];

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addEventListener(DocumentParsedEvent::class, $this->colorMarks(...));
    }

    private function colorMarks(DocumentParsedEvent $event): void
    {
        $pattern = '/^\{('.implode('|', self::COLORS).')\}/';

        foreach ($event->getDocument()->iterator() as $node) {
            $next = $node instanceof Mark ? $node->next() : null;

            if (! $next instanceof Text || preg_match($pattern, $next->getLiteral(), $match) !== 1) {
                continue;
            }

            $node->data->set('attributes/class', 'mark-'.$match[1]);

            $rest = substr($next->getLiteral(), strlen($match[0]));
            $rest === '' ? $next->detach() : $next->setLiteral($rest);
        }
    }
}
