<?php

namespace Coleus\Notes\Markdown;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Node;
use League\CommonMark\Node\NodeIterator;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

/**
 * Smart embeds (B6.1): a YouTube, Vimeo, Loom, Figma, Miro or Google Maps
 * address alone on its line plays or shows in place. Only these services
 * are allowed. Their embed address is built from the link itself (no
 * oEmbed HTML from elsewhere is ever trusted), and the frame is sandboxed:
 * it may run its own scripts on its own origin, nothing on ours.
 */
class SmartEmbeds implements ExtensionInterface, NodeRendererInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        // Before the link-preview marker (-10), so these become players rather than cards.
        $environment->addEventListener(DocumentParsedEvent::class, [$this, 'onDocumentParsed'], -5);
        $environment->addRenderer(SmartEmbed::class, $this);
    }

    public function onDocumentParsed(DocumentParsedEvent $event): void
    {
        $links = [];

        foreach ($event->getDocument()->iterator(NodeIterator::FLAG_BLOCKS_ONLY) as $node) {
            $link = $node->firstChild();

            if ($link instanceof Link && $link->next() === null && $node instanceof Paragraph) {
                $links[] = $link;
            }
        }

        foreach ($links as $link) {
            $text = $link->firstChild();
            $bare = $text instanceof Text && $text->next() === null && rtrim($text->getLiteral(), '/') === rtrim($link->getUrl(), '/');
            $embed = $bare ? self::embed($link->getUrl()) : null;

            if ($embed !== null) {
                $link->replaceWith(new SmartEmbed($link->getUrl(), $embed['src'], $embed['provider']));
            }
        }
    }

    /**
     * The embed for an allowed service's link, or null.
     *
     * @return array{provider: string, src: string}|null
     */
    public static function embed(string $url): ?array
    {
        $parts = parse_url($url);
        $host = strtolower(preg_replace('/^www\./', '', (string) ($parts['host'] ?? '')) ?? '');
        $path = (string) ($parts['path'] ?? '');
        parse_str((string) ($parts['query'] ?? ''), $query);

        if (! in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)) {
            return null;
        }

        $id = fn (string $pattern, string $subject): ?string => preg_match($pattern, $subject, $match) === 1 ? $match[1] : null;

        $youtube = match (true) {
            in_array($host, ['youtube.com', 'm.youtube.com'], true) && $path === '/watch' => is_string($query['v'] ?? null) ? $query['v'] : null,
            in_array($host, ['youtube.com', 'm.youtube.com'], true) => $id('#^/(?:shorts|embed|live)/([\w-]{6,20})#', $path),
            $host === 'youtu.be' => $id('#^/([\w-]{6,20})#', $path),
            default => null,
        };

        if ($youtube !== null && preg_match('/^[\w-]{6,20}$/', $youtube) === 1) {
            return ['provider' => 'youtube', 'src' => 'https://www.youtube-nocookie.com/embed/'.$youtube];
        }

        return match (true) {
            $host === 'vimeo.com' && ($video = $id('#^/(\d{5,12})#', $path)) !== null => ['provider' => 'vimeo', 'src' => 'https://player.vimeo.com/video/'.$video],
            $host === 'loom.com' && ($loom = $id('#^/share/([0-9a-f]{20,40})#i', $path)) !== null => ['provider' => 'loom', 'src' => 'https://www.loom.com/embed/'.$loom],
            $host === 'figma.com' && preg_match('#^/(?:file|design|proto|board)/[\w-]+#', $path) === 1 => ['provider' => 'figma', 'src' => 'https://www.figma.com/embed?embed_host=coleus&url='.rawurlencode($url)],
            $host === 'miro.com' && ($board = $id('#^/app/board/([\w=-]{6,40})#', $path)) !== null => ['provider' => 'miro', 'src' => 'https://miro.com/app/live-embed/'.$board.'/'],
            in_array($host, ['google.com', 'maps.google.com'], true) && str_starts_with($path, '/maps') => self::map($path, $query),
            default => null,
        };
    }

    /**
     * Google Maps without an API key: the place name, or the coordinates,
     * as a search shown in the keyless embed.
     *
     * @param  array<string, mixed>  $query
     * @return array{provider: string, src: string}|null
     */
    private static function map(string $path, array $query): ?array
    {
        $place = preg_match('#/maps/place/([^/]+)#', $path, $match) === 1 ? urldecode(str_replace('+', ' ', $match[1])) : null;
        $coordinates = preg_match('#@(-?\d+\.\d+),(-?\d+\.\d+)#', $path, $at) === 1 ? $at[1].','.$at[2] : null;
        $search = $place ?? (is_string($query['q'] ?? null) ? $query['q'] : null) ?? $coordinates;

        return $search === null ? null : ['provider' => 'maps', 'src' => 'https://maps.google.com/maps?output=embed&q='.rawurlencode($search)];
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        SmartEmbed::assertInstanceOf($node);

        return new HtmlElement('iframe', [
            'src' => $node->src,
            'title' => $node->provider.' embed of '.$node->url,
            'class' => 'smart-embed smart-embed-'.$node->provider,
            'sandbox' => 'allow-scripts allow-same-origin allow-presentation allow-popups allow-popups-to-escape-sandbox',
            'allow' => 'fullscreen; picture-in-picture; encrypted-media; clipboard-write',
            'referrerpolicy' => 'strict-origin-when-cross-origin',
            'loading' => 'lazy',
            'data-source' => $node->url,
        ], '');
    }
}
