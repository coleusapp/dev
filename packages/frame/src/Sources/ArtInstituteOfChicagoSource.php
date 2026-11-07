<?php

namespace Coleus\Frame\Sources;

use Coleus\Frame\Contracts\ArtworkSource;
use Coleus\Frame\Data\ArtworkData;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

class ArtInstituteOfChicagoSource implements ArtworkSource
{
    public const int MaxSearchWindow = 10_000;

    public function key(): string
    {
        return 'aic';
    }

    public function name(): string
    {
        return 'Art Institute of Chicago';
    }

    /** @return Collection<int, ArtworkData> */
    public function search(?string $query, int $count): Collection
    {
        $total = min($this->searchPage($query, page: 1, limit: 1)['pagination']['total'] ?? 0, self::MaxSearchWindow);

        if (!$total) {
            return collect();
        }

        $lastPage = max(1, intdiv($total, $count));

        return collect($this->searchPage($query, page: random_int(1, $lastPage), limit: $count)['data'] ?? [])
            ->filter(fn (array $result) => filled($result['image_id'] ?? null))
            ->map(fn (array $result) => new ArtworkData(
                source_id: $result['id'],
                title: $result['title'] ?: 'Untitled',
                artist_display: $result['artist_display'] ?? null,
                date_display: $result['date_display'] ?? null,
                image_url: config('frame.aic.iiif_url')."/{$result['image_id']}/full/1686,/0/default.jpg",
            ))
            ->values();
    }

    public function image(ArtworkData $artwork): string
    {
        try {
            return $this->client()
                ->get($artwork->image_url)
                ->throw()
                ->body();
        } finally {
            Sleep::for(1)->second();
        }
    }

    /**
     * @return array{pagination?: array{total?: int}, data?: list<array{id: int, title: ?string, artist_display: ?string, date_display: ?string, image_id: ?string}>}
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    private function searchPage(?string $query, int $page, int $limit): array
    {
        $body = [
            'query' => [
                'bool' => [
                    'filter' => [
                        ['term' => ['is_public_domain' => true]],
                        ['term' => ['artwork_type_title.keyword' => 'Painting']],
                        ['exists' => ['field' => 'image_id']],
                    ],
                ],
            ],
            'fields' => ['id', 'title', 'artist_display', 'date_display', 'image_id'],
            'page' => $page,
            'limit' => $limit,
        ];

        if (filled($query)) {
            $body['q'] = $query;
        }

        return $this->client()
            ->post(config('frame.aic.api_url').'/artworks/search', $body)
            ->throw()
            ->json();
    }

    private function client(): PendingRequest
    {
        return Http::withHeaders(['AIC-User-Agent' => config('frame.aic.user_agent')])
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(30);
    }
}
