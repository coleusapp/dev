<?php

namespace Coleus\Frame\Jobs;

use Coleus\Frame\Models\Frame;
use Coleus\Frame\Models\FrameArtwork;
use Coleus\Frame\Support\ImageFitter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Spatie\Multitenancy\Jobs\NotTenantAware;
use Throwable;

/**
 * Adds up to $count new public-domain paintings from the Art Institute of
 * Chicago to a frame's pool, then prunes the pool down to frame.pool_size.
 *
 * A random results page is picked so repeated fetches don't keep returning
 * the same artworks. An image that fails to download or decode is logged
 * and skipped; a failed search fails the job. Frames are per user, not per
 * tenant, so the job opts out of tenant-aware queueing.
 */
class FetchFrameArtworks implements NotTenantAware, ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The AIC search API refuses to page past this many results.
     */
    public const int MAX_SEARCH_WINDOW = 10_000;

    public int $tries = 1;

    public int $timeout = 900;

    public int $uniqueFor = 900;

    public function __construct(public Frame $frame, public int $count = 20) {}

    public function uniqueId(): string
    {
        return (string) $this->frame->id;
    }

    public function handle(): void
    {
        $total = min($this->search(page: 1, limit: 1)['pagination']['total'] ?? 0, self::MAX_SEARCH_WINDOW);

        if ($total === 0) {
            return;
        }

        $lastPage = max(1, intdiv($total, $this->count));
        $results = $this->search(page: random_int(1, $lastPage), limit: $this->count)['data'] ?? [];

        $existingSourceIds = $this->frame->artworks()->pluck('source_id')->all();

        foreach ($results as $result) {
            if (empty($result['image_id']) || in_array($result['id'], $existingSourceIds, true)) {
                continue;
            }

            $this->store($result);

            Sleep::for(1)->second();
        }

        $this->prune();
    }

    /**
     * @return array{pagination?: array{total?: int}, data?: list<array{id: int, title: ?string, artist_display: ?string, date_display: ?string, image_id: ?string}>}
     */
    private function search(int $page, int $limit): array
    {
        $filters = [
            ['term' => ['is_public_domain' => true]],
            ['term' => ['artwork_type_title.keyword' => 'Painting']],
            ['exists' => ['field' => 'image_id']],
        ];

        $body = [
            'query' => ['bool' => ['filter' => $filters]],
            'fields' => ['id', 'title', 'artist_display', 'date_display', 'image_id'],
            'page' => $page,
            'limit' => $limit,
        ];

        if (filled($this->frame->search_query)) {
            $body['q'] = $this->frame->search_query;
        }

        return $this->client()
            ->post(config('frame.aic.api_url').'/artworks/search', $body)
            ->throw()
            ->json();
    }

    /**
     * @param  array{id: int, title: ?string, artist_display: ?string, date_display: ?string, image_id: string}  $result
     */
    private function store(array $result): void
    {
        $imageUrl = config('frame.aic.iiif_url')."/{$result['image_id']}/full/1686,/0/default.jpg";

        try {
            $jpeg = ImageFitter::fitAsJpeg(
                $this->client()->get($imageUrl)->throw()->body(),
                config('frame.display.width'),
                config('frame.display.height'),
            );
        } catch (Throwable $exception) {
            Log::warning('Frame artwork image skipped.', [
                'frame_id' => $this->frame->id,
                'source_id' => $result['id'],
                'url' => $imageUrl,
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        $path = $this->frame->artworkDirectory()."/{$result['id']}.jpg";

        Storage::disk(config('frame.disk'))->put($path, $jpeg);

        $this->frame->artworks()->create([
            'source_id' => $result['id'],
            'title' => Str::limit($result['title'] ?: 'Untitled', 255, ''),
            'artist_display' => $this->limitOrNull($result['artist_display'] ?? null),
            'date_display' => $this->limitOrNull($result['date_display'] ?? null),
            'path' => $path,
        ]);
    }

    private function prune(): void
    {
        $excess = $this->frame->artworks()->count() - config('frame.pool_size');

        if ($excess <= 0) {
            return;
        }

        $this->frame->artworks()
            ->oldest()
            ->oldest('id')
            ->limit($excess)
            ->get()
            ->each(function (FrameArtwork $artwork): void {
                Storage::disk(config('frame.disk'))->delete($artwork->path);
                $artwork->delete();
            });
    }

    private function client(): PendingRequest
    {
        return Http::withHeaders(['AIC-User-Agent' => config('frame.aic.user_agent')])
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(30);
    }

    private function limitOrNull(?string $value): ?string
    {
        return blank($value) ? null : Str::limit($value, 255, '');
    }
}
