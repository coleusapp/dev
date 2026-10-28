<?php

namespace Coleus\Notes\LinkPreviews;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Fetches a web page for a link preview (A4.1) and reads its title,
 * description, image and site name (Open Graph first, then <title> and
 * the description meta tag).
 *
 * Every hop goes through UrlGuard and is pinned to the checked address;
 * redirects are followed by hand (at most 3), each checked again. Short
 * timeouts, only HTML, and only the first 512 KB are read.
 */
class LinkPreviewFetcher
{
    public const int MAX_BYTES = 512 * 1024;

    public const int MAX_REDIRECTS = 3;

    public function __construct(private readonly UrlGuard $guard)
    {
        //
    }

    /**
     * @return array{url: string, title: string|null, description: string|null, image: string|null, site_name: string|null}|null
     */
    public function fetch(string $url): ?array
    {
        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            $target = $this->guard->check($url);

            if ($target === null) {
                return null;
            }

            try {
                $response = Http::withOptions([
                    'allow_redirects' => false,
                    'stream' => true,
                    'curl' => [CURLOPT_RESOLVE => ["{$target['host']}:{$target['port']}:{$target['ip']}"]],
                ])
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; ColeusNotes/1.0; link preview)', 'Accept' => 'text/html'])
                    ->connectTimeout(3)
                    ->timeout(5)
                    ->get($url);
            } catch (Throwable) {
                return null;
            }

            if ($response->redirect()) {
                $location = (string) $response->header('Location');

                if ($location === '') {
                    return null;
                }

                $url = self::absolute($location, $url);

                continue;
            }

            if (! $response->successful() || ! str_contains(strtolower((string) $response->header('Content-Type')), 'text/html')) {
                return null;
            }

            $body = $response->toPsrResponse()->getBody();
            $html = '';

            while (! $body->eof() && strlen($html) < self::MAX_BYTES) {
                $html .= $body->read(8192);
            }

            $body->close();

            return self::read(substr($html, 0, self::MAX_BYTES), $url);
        }

        return null;
    }

    /**
     * @return array{url: string, title: string|null, description: string|null, image: string|null, site_name: string|null}
     */
    public static function read(string $html, string $url): array
    {
        $meta = [];

        if (preg_match_all('/<meta\s[^>]*>/i', $html, $tags)) {
            foreach ($tags[0] as $tag) {
                if (preg_match('/\b(?:property|name)\s*=\s*["\']([^"\']+)["\']/i', $tag, $key) && preg_match('/\bcontent\s*=\s*(["\'])(.*?)\1/is', $tag, $content)) {
                    $meta[strtolower($key[1])] ??= html_entity_decode($content[2], ENT_QUOTES | ENT_HTML5);
                }
            }
        }

        $title = $meta['og:title'] ?? $meta['twitter:title'] ?? (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $match) ? html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5) : null);
        $image = $meta['og:image'] ?? $meta['twitter:image'] ?? null;
        $image = $image === null ? null : self::absolute($image, $url);

        return [
            'url' => $url,
            'title' => self::clean($title, 200),
            'description' => self::clean($meta['og:description'] ?? $meta['description'] ?? $meta['twitter:description'] ?? null, 300),
            'image' => $image !== null && preg_match('#^https?://#i', $image) ? Str::limit($image, 2000, '') : null,
            'site_name' => self::clean($meta['og:site_name'] ?? null, 100) ?? parse_url($url, PHP_URL_HOST),
        ];
    }

    private static function clean(?string $text, int $length): ?string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $text)));

        return $text === '' ? null : Str::limit($text, $length);
    }

    private static function absolute(string $location, string $base): string
    {
        if (preg_match('#^https?://#i', $location)) {
            return $location;
        }

        $parts = parse_url($base);
        $origin = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '').(isset($parts['port']) ? ':'.$parts['port'] : '');

        if (str_starts_with($location, '//')) {
            return ($parts['scheme'] ?? 'https').':'.$location;
        }

        return str_starts_with($location, '/') ? $origin.$location : rtrim($origin.dirname($parts['path'] ?? '/'), '/').'/'.$location;
    }
}
