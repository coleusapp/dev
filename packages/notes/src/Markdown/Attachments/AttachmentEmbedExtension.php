<?php

namespace Coleus\Notes\Markdown\Attachments;

use Coleus\Notes\Enums\AttachmentKind;
use Coleus\Notes\Models\Attachment;
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
 * Attachments embedded by name (O3.2, Obsidian style): ![[photo.png]],
 * sized with ![[photo.png|300]] (width) or ![[photo.png|300x200]].
 * Whether it's a file is decided by its ending alone, so parsing needs no
 * database; the file is looked up (the user's newest attachment with that
 * name, any case) when the note is rendered.
 */
class AttachmentEmbedExtension implements ExtensionInterface, InlineParserInterface, NodeRendererInterface
{
    public const string FILE_ENDING = '/\.(png|jpe?g|gif|webp|avif|heic|heif|svg|mp4|webm|mov|m4v|ogv|mp3|m4a|aac|wav|ogg|oga|weba|flac|pdf)$/i';

    public function register(EnvironmentBuilderInterface $environment): void
    {
        // Above the wiki link (200) and image ("!") parsers.
        $environment->addInlineParser($this, 250);
        $environment->addRenderer(AttachmentEmbed::class, $this);
    }

    public static function isFileName(string $name): bool
    {
        return preg_match(self::FILE_ENDING, trim($name)) === 1;
    }

    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::regex('!\[\[([^\[\]\n|]{1,255})(?:\|\s*(\d{1,4})(?:\s*x\s*(\d{1,4}))?\s*)?\]\]');
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        [$name, $width, $height] = array_pad($inlineContext->getSubMatches(), 3, null);

        if (! self::isFileName((string) $name)) {
            return false;
        }

        $inlineContext->getCursor()->advanceBy($inlineContext->getFullMatchLength());
        $inlineContext->getContainer()->appendChild(new AttachmentEmbed(trim((string) $name), $width ? (int) $width : null, $height ? (int) $height : null));

        return true;
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        AttachmentEmbed::assertInstanceOf($node);

        $attachment = Attachment::query()->whereRaw('lower(name) = ?', [mb_strtolower($node->name)])->latest('id')->first();

        if ($attachment === null) {
            return new HtmlElement('span', ['class' => 'attachment-missing', 'title' => 'No attachment named "'.$node->name.'"'], Xml::escape($node->name));
        }

        if ($attachment->kind !== AttachmentKind::Image) {
            return MediaPlayer::for($attachment->url(), $attachment->name, $node->width, $node->height)
                ?? new HtmlElement('a', ['href' => $attachment->url(), 'class' => 'attachment-link'], Xml::escape($attachment->name));
        }

        $src = $attachment->url();
        $widths = array_keys($attachment->variants ?? []);

        return new HtmlElement('img', [
            'src' => $src,
            'alt' => $attachment->name,
            'class' => 'attachment-embed',
            'loading' => 'lazy',
            ...($node->width ? ['width' => (string) $node->width] : []),
            ...($node->height ? ['height' => (string) $node->height] : []),
            ...($widths === [] ? [] : [
                'srcset' => implode(', ', array_map(fn (string $width) => "{$src}?w={$width} {$width}w", $widths)).($attachment->width ? ", {$src} {$attachment->width}w" : ''),
                'sizes' => $node->width ? "{$node->width}px" : '(max-width: 768px) 100vw, 768px',
            ]),
        ], '', true);
    }
}
