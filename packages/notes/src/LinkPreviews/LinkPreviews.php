<?php

namespace Coleus\Notes\LinkPreviews;

use Coleus\Notes\Models\LinkPreview;

/**
 * Link previews with a cache (A4.1): a page is fetched at most once a
 * week (a failed one once a day), whoever asks.
 */
class LinkPreviews
{
    public const int FRESH_DAYS = 7;

    public const int FAILED_RETRY_HOURS = 24;

    public function __construct(private readonly LinkPreviewFetcher $fetcher)
    {
        //
    }

    public function get(string $url): ?LinkPreview
    {
        $hash = hash('sha256', $url);
        $cached = LinkPreview::query()->where('url_hash', $hash)->first();

        if ($cached && $cached->fetched_at->isAfter($cached->failed ? now()->subHours(self::FAILED_RETRY_HOURS) : now()->subDays(self::FRESH_DAYS))) {
            return $cached;
        }

        $found = $this->fetcher->fetch($url);

        return LinkPreview::query()->updateOrCreate(['url_hash' => $hash], [
            'url' => $url,
            'title' => $found['title'] ?? null,
            'description' => $found['description'] ?? null,
            'image' => $found['image'] ?? null,
            'site_name' => $found['site_name'] ?? null,
            'failed' => $found === null || ($found['title'] === null && $found['description'] === null),
            'fetched_at' => now(),
        ]);
    }
}
