<?php

namespace Coleus\Frame\Sources;

use Coleus\Frame\Contracts\ArtworkSource;
use Coleus\Frame\Data\ArtworkData;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

/**
 * CC0 paintings from the Cleveland Museum of Art Open Access API. The "print"
 * image (about 3400 px) is preferred; "web" (900 px) is the fallback.
 */
class ClevelandMuseumOfArtSource implements ArtworkSource
{
    public function key(): string
    {
        return 'cleveland';
    }

    public function name(): string
    {
        return 'Cleveland Museum of Art';
    }

    /**
     * @return Collection<int, ArtworkData>
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    public function search(?string $query, int $count): Collection
    {
        $total = $this->searchPage($query, skip: 0, limit: 1)['info']['total'] ?? 0;

        if (!$total) {
            return collect();
        }

        $skip = random_int(0, max(0, $total - $count));

        return collect($this->searchPage($query, $skip, $count)['data'] ?? [])
            ->map(fn (array $result) => [...$result, 'image_url' => $result['images']['print']['url'] ?? $result['images']['web']['url'] ?? null])
            ->filter(fn (array $result) => filled($result['image_url']))
            ->map(fn (array $result) => new ArtworkData(
                source_id: $result['id'],
                title: $result['title'] ?: 'Untitled',
                artist_display: $result['creators'][0]['description'] ?? null,
                date_display: $result['creation_date'] ?? null,
                image_url: $result['image_url'],
            ))
            ->values();
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function image(ArtworkData $artwork): string
    {
        return $this->client()
            ->get($artwork->image_url)
            ->throw()
            ->body();
    }

    /**
     * @return array{info?: array{total?: int}, data?: list<array{id: int, title: ?string, creation_date: ?string, creators?: list<array{description: ?string}>, images?: ?array<string, ?array{url: string}>}>}
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    private function searchPage(?string $query, int $skip, int $limit): array
    {
        $parameters = [
            'type' => 'Painting',
            'has_image' => 1,
            'cc0' => 1,
            'fields' => 'id,title,creators,creation_date,images',
            'skip' => $skip,
            'limit' => $limit,
        ];

        if (filled($query)) {
            $parameters['q'] = $query;
        }

        return $this->client()
            ->get(config('frame.cleveland.api_url').'/artworks/', $parameters)
            ->throw()
            ->json();
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()
            ->connectTimeout(10)
            ->timeout(60);
    }
}
