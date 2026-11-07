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
 * The search only returns object ids, so each result costs one more request
 * for its details; objects that aren't public domain have no primaryImage.
 */
class MetropolitanMuseumOfArtSource implements ArtworkSource
{
    /**
     * The v1.1 search refuses offset + limit beyond this.
     */
    public const int MaxSearchWindow = 10_000;

    public function key(): string
    {
        return 'met';
    }

    public function name(): string
    {
        return 'The Metropolitan Museum of Art';
    }

    /**
     * @return Collection<int, ArtworkData>
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    public function search(?string $query, int $count): Collection
    {
        $total = min($this->searchPage($query, offset: 0, limit: 1)['total'] ?? 0, self::MaxSearchWindow);

        if (!$total) {
            return collect();
        }

        $offset = random_int(0, max(0, $total - $count));

        return collect($this->searchPage($query, $offset, $count)['objectIDs'] ?? [])
            ->map(fn (int $objectId) => $this->object($objectId))
            ->filter(fn (?array $object) => ($object['isPublicDomain'] ?? false) && filled($object['primaryImage'] ?? null))
            ->map(fn (array $object) => new ArtworkData(
                source_id: $object['objectID'],
                title: $object['title'] ?: 'Untitled',
                artist_display: $object['artistDisplayName'] ?? null,
                date_display: $object['objectDate'] ?? null,
                image_url: $object['primaryImage'],
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
     * @return array{total?: int, objectIDs?: ?list<int>}
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    private function searchPage(?string $query, int $offset, int $limit): array
    {
        $parameters = [
            'medium' => 'Paintings',
            'hasImages' => 'true',
            'offset' => $offset,
            'limit' => $limit,
        ];

        if (filled($query)) {
            $parameters['q'] = $query;
        }

        return $this->client()
            ->get(config('frame.met.api_url').'/v1.1/search', $parameters)
            ->throw()
            ->json();
    }

    /**
     * A missing or failing object is skipped rather than failing the search.
     *
     * @return ?array{objectID: int, title: ?string, artistDisplayName: ?string, objectDate: ?string, primaryImage: ?string, isPublicDomain: bool}
     *
     * @throws ConnectionException
     */
    private function object(int $objectId): ?array
    {
        $response = $this->client()->get(config('frame.met.api_url')."/v1/objects/{$objectId}");

        return $response->successful() ? $response->json() : null;
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()
            ->connectTimeout(10)
            ->timeout(60);
    }
}
