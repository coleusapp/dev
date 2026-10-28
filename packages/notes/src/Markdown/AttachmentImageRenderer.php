<?php

namespace Coleus\Notes\Markdown;

use Coleus\Notes\Markdown\Attachments\MediaPlayer;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Renderer\Inline\ImageRenderer;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\Config\ConfigurationAwareInterface;
use League\Config\ConfigurationInterface;

/**
 * Images load lazily, and images attached to notes (O3.1c) come with a
 * srcset of their smaller WebP copies, so phones don't download the full
 * picture. The attachment route falls back to the original for widths an
 * image has no copy at.
 */
class AttachmentImageRenderer implements ConfigurationAwareInterface, NodeRendererInterface
{
    private const string ATTACHMENT = '#^/notes/attachments/[0-9a-f-]{36}/#';

    private readonly ImageRenderer $images;

    public function __construct()
    {
        $this->images = new ImageRenderer;
    }

    public function setConfiguration(ConfigurationInterface $configuration): void
    {
        $this->images->setConfiguration($configuration);
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        Image::assertInstanceOf($node);

        $element = $this->images->render($node, $childRenderer);

        if (! $element instanceof HtmlElement) {
            return $element;
        }

        $src = (string) $element->getAttribute('src');

        // ![clip.mp4](/notes/attachments/…) of attached video, audio or a PDF plays in place (O3.4).
        if (preg_match(self::ATTACHMENT, $src) === 1) {
            $player = MediaPlayer::for($src, (string) $element->getAttribute('alt'));

            if ($player !== null) {
                return $player;
            }
        }

        $element->setAttribute('loading', 'lazy');

        if (preg_match(self::ATTACHMENT, $src) === 1) {
            $widths = (array) config('notes.attachments.image_widths');
            $separator = str_contains($src, '?') ? '&' : '?';
            $element->setAttribute('srcset', implode(', ', array_map(fn (int $width) => "{$src}{$separator}w={$width} {$width}w", $widths)));
            $element->setAttribute('sizes', '(max-width: 768px) 100vw, 768px');
        }

        return $element;
    }
}
