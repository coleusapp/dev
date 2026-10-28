<?php

use Coleus\Notes\Markdown\NoteMarkdown;
use Coleus\Notes\Markdown\SmartEmbeds;
use Tests\TestCase;

uses(TestCase::class);

it('builds embeds for the allowed services', function (string $url, string $provider, string $src) {
    expect(SmartEmbeds::embed($url))->toBe(['provider' => $provider, 'src' => $src]);
})->with([
    'youtube watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10', 'youtube', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
    'youtube short link' => ['https://youtu.be/dQw4w9WgXcQ', 'youtube', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
    'youtube shorts' => ['https://youtube.com/shorts/abcdEFGH123', 'youtube', 'https://www.youtube-nocookie.com/embed/abcdEFGH123'],
    'vimeo' => ['https://vimeo.com/76979871', 'vimeo', 'https://player.vimeo.com/video/76979871'],
    'loom' => ['https://www.loom.com/share/0123456789abcdef0123456789abcdef', 'loom', 'https://www.loom.com/embed/0123456789abcdef0123456789abcdef'],
    'figma' => ['https://www.figma.com/design/AbC123/Plan', 'figma', 'https://www.figma.com/embed?embed_host=coleus&url=https%3A%2F%2Fwww.figma.com%2Fdesign%2FAbC123%2FPlan'],
    'miro' => ['https://miro.com/app/board/uXjVO123abc=/', 'miro', 'https://miro.com/app/live-embed/uXjVO123abc=/'],
    'maps place' => ['https://www.google.com/maps/place/Torre+de+Bel%C3%A9m/@38.69,-9.21,17z', 'maps', 'https://maps.google.com/maps?output=embed&q=Torre%20de%20Bel%C3%A9m'],
    'maps coordinates' => ['https://www.google.com/maps/@38.7223,-9.1393,14z', 'maps', 'https://maps.google.com/maps?output=embed&q=38.7223%2C-9.1393'],
]);

it('refuses everything else', function (string $url) {
    expect(SmartEmbeds::embed($url))->toBeNull();
})->with([
    'other site' => ['https://example.test/watch?v=dQw4w9WgXcQ'],
    'look-alike host' => ['https://youtube.com.evil.test/watch?v=dQw4w9WgXcQ'],
    'youtube home' => ['https://www.youtube.com/'],
    'bad youtube id' => ['https://www.youtube.com/watch?v="><script>'],
    'javascript' => ['javascript://youtube.com/watch?v=dQw4w9WgXcQ'],
    'vimeo page' => ['https://vimeo.com/channels'],
]);

it('shows an allowed link alone on its line as a sandboxed frame, and leaves other links', function () {
    expect(NoteMarkdown::toHtml('https://youtu.be/dQw4w9WgXcQ'))
        ->toContain('<iframe src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ"')
        ->toContain('sandbox="allow-scripts allow-same-origin allow-presentation allow-popups allow-popups-to-escape-sandbox"')
        ->toContain('loading="lazy"')
        ->not->toContain('data-link-preview')
        ->and(NoteMarkdown::toHtml('watch https://youtu.be/dQw4w9WgXcQ now'))->not->toContain('<iframe')
        ->and(NoteMarkdown::toHtml('[the talk](https://youtu.be/dQw4w9WgXcQ)'))->not->toContain('<iframe')
        ->and(NoteMarkdown::toHtml('https://example.test/'))->toContain('data-link-preview');
});
