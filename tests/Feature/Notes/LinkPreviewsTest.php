<?php

use Coleus\Notes\LinkPreviews\LinkPreviewFetcher;
use Coleus\Notes\LinkPreviews\UrlGuard;
use Coleus\Notes\Markdown\NoteMarkdown;
use Coleus\Notes\Models\LinkPreview;
use Coleus\Users\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    // Fake DNS: example.test is public, internal.test points inside, rebind.test has both.
    $this->app->instance(UrlGuard::class, new UrlGuard(fn (string $host) => match ($host) {
        'example.test', 'other.test' => ['93.184.216.34'],
        'internal.test' => ['10.0.0.5'],
        'rebind.test' => ['93.184.216.34', '127.0.0.1'],
        default => [],
    }));
});

it('refuses URLs that reach into private networks', function (string $url) {
    expect(app(UrlGuard::class)->check($url))->toBeNull();
})->with([
    'loopback' => ['http://127.0.0.1/'],
    'private 10' => ['http://10.1.2.3/'],
    'private 192.168' => ['http://192.168.0.10/'],
    'cloud metadata' => ['http://169.254.169.254/latest/meta-data/'],
    'carrier NAT' => ['http://100.64.0.1/'],
    'ipv6 loopback' => ['http://[::1]/'],
    'ipv6 unique local' => ['http://[fd00::1]/'],
    'ipv4-mapped ipv6' => ['http://[::ffff:127.0.0.1]/'],
    'name resolving inside' => ['https://internal.test/'],
    'name with one inside address' => ['https://rebind.test/'],
    'unknown name' => ['https://nowhere.test/'],
    'other port' => ['http://example.test:8080/'],
    'other scheme' => ['ftp://example.test/'],
    'credentials' => ['https://user:pass@example.test/'],
]);

it('lets public addresses through, pinned to the checked IP', function () {
    expect(app(UrlGuard::class)->check('https://example.test/page'))->toBe(['host' => 'example.test', 'port' => 443, 'ip' => '93.184.216.34'])
        ->and(app(UrlGuard::class)->check('http://93.184.216.34/'))->not->toBeNull();
});

it('reads Open Graph, falling back to title and description', function () {
    Http::fake(['https://example.test/*' => Http::response('<html><head><title>Plain &amp; title</title><meta name="description" content="About it"><meta property="og:image" content="/img/card.png"></head></html>', 200, ['Content-Type' => 'text/html; charset=utf-8'])]);

    $this->getJson(route('notes.link-preview', ['url' => 'https://example.test/post']))
        ->assertOk()
        ->assertJsonPath('data', ['url' => 'https://example.test/post', 'title' => 'Plain & title', 'description' => 'About it', 'image' => 'https://example.test/img/card.png', 'site_name' => 'example.test']);
});

it('follows redirects only to allowed places, and refuses non-HTML', function () {
    Http::fake([
        'https://example.test/go' => Http::response('', 302, ['Location' => 'https://other.test/landing']),
        'https://other.test/landing' => Http::response('<meta property="og:title" content="Landed">', 200, ['Content-Type' => 'text/html']),
        'https://example.test/sneaky' => Http::response('', 301, ['Location' => 'http://169.254.169.254/latest/meta-data/']),
        'https://example.test/file.zip' => Http::response('zip', 200, ['Content-Type' => 'application/zip']),
    ]);

    expect(app(LinkPreviewFetcher::class)->fetch('https://example.test/go'))->toMatchArray(['url' => 'https://other.test/landing', 'title' => 'Landed'])
        ->and(app(LinkPreviewFetcher::class)->fetch('https://example.test/sneaky'))->toBeNull()
        ->and(app(LinkPreviewFetcher::class)->fetch('https://example.test/file.zip'))->toBeNull();

    Http::assertNotSent(fn ($request) => str_contains($request->url(), '169.254'));
});

it('caches previews, failures for less time', function () {
    Http::fake(['https://example.test/*' => Http::response('<title>Cached</title>', 200, ['Content-Type' => 'text/html'])]);

    $this->getJson(route('notes.link-preview', ['url' => 'https://example.test/a']))->assertJsonPath('data.title', 'Cached');
    $this->getJson(route('notes.link-preview', ['url' => 'https://example.test/a']))->assertJsonPath('data.title', 'Cached');
    Http::assertSentCount(1);

    $this->getJson(route('notes.link-preview', ['url' => 'https://internal.test/']))->assertJsonPath('data', null);
    expect(LinkPreview::query()->where('url', 'https://internal.test/')->value('failed'))->toBeTrue();
});

it('marks a bare address on its own line for a card, and nothing else', function () {
    expect(NoteMarkdown::toHtml('https://example.test/post'))->toContain('<a data-link-preview="" class="link-preview" href="https://example.test/post">')
        ->and(NoteMarkdown::toHtml('see https://example.test/post here'))->not->toContain('data-link-preview')
        ->and(NoteMarkdown::toHtml('[my words](https://example.test/post)'))->not->toContain('data-link-preview');
});
