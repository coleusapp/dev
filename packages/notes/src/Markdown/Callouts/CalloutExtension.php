<?php

namespace Coleus\Notes\Markdown\Callouts;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Block\BlockQuote;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\Newline;
use League\CommonMark\Node\Node;
use League\CommonMark\Node\NodeIterator;
use League\CommonMark\Node\StringContainerInterface;

/**
 * Turns "> [!type] Title" blockquotes into callouts (O5.1). The marker and
 * title are the blockquote's first line; the rest of it is the body.
 * Types share colours by family (Obsidian's aliases: tldr is an abstract,
 * caution a warning…); unknown types look like notes.
 */
class CalloutExtension implements ExtensionInterface
{
    /**
     * @var array<string, list<string>>
     */
    public const array FAMILIES = [
        'note' => ['note'],
        'abstract' => ['abstract', 'summary', 'tldr'],
        'info' => ['info'],
        'todo' => ['todo'],
        'tip' => ['tip', 'hint', 'important'],
        'success' => ['success', 'check', 'done'],
        'question' => ['question', 'help', 'faq'],
        'warning' => ['warning', 'caution', 'attention'],
        'failure' => ['failure', 'fail', 'missing'],
        'danger' => ['danger', 'error'],
        'bug' => ['bug'],
        'example' => ['example'],
        'quote' => ['quote', 'cite'],
    ];

    private const string MARKER = '/^\[!([A-Za-z][\w-]*)\]([+-]?)[ \t]*(.*)$/u';

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addEventListener(DocumentParsedEvent::class, [$this, 'onDocumentParsed']);
        $environment->addRenderer(Callout::class, new CalloutRenderer);
    }

    public function onDocumentParsed(DocumentParsedEvent $event): void
    {
        $quotes = [];

        foreach ($event->getDocument()->iterator(NodeIterator::FLAG_BLOCKS_ONLY) as $node) {
            if ($node instanceof BlockQuote) {
                $quotes[] = $node;
            }
        }

        foreach ($quotes as $quote) {
            $this->convert($quote);
        }
    }

    public static function family(string $type): string
    {
        foreach (self::FAMILIES as $family => $types) {
            if (in_array($type, $types, true)) {
                return $family;
            }
        }

        return 'note';
    }

    private function convert(BlockQuote $quote): void
    {
        $paragraph = $quote->firstChild();

        if (! $paragraph instanceof Paragraph) {
            return;
        }

        $firstLine = [];

        foreach ($paragraph->children() as $child) {
            if ($child instanceof Newline) {
                break;
            }

            $firstLine[] = $child;
        }

        $text = implode('', array_map(fn (Node $node) => self::plainText($node), $firstLine));

        if (preg_match(self::MARKER, $text, $match) !== 1) {
            return;
        }

        $type = strtolower($match[1]);
        $callout = new Callout($type, self::family($type), $match[2], trim($match[3]) !== '' ? trim($match[3]) : ucfirst($type));
        $callout->setStartLine($quote->getStartLine());
        $callout->setEndLine($quote->getEndLine());

        foreach ($firstLine as $node) {
            $node->detach();
        }

        if ($paragraph->firstChild() instanceof Newline) {
            $paragraph->firstChild()->detach();
        }

        if ($paragraph->firstChild() === null) {
            $paragraph->detach();
        }

        foreach ($quote->children() as $child) {
            $callout->appendChild($child);
        }

        $quote->replaceWith($callout);
    }

    private static function plainText(Node $node): string
    {
        if ($node instanceof StringContainerInterface) {
            return $node->getLiteral();
        }

        return implode('', array_map(fn (Node $child) => self::plainText($child), iterator_to_array($node->children())));
    }
}
