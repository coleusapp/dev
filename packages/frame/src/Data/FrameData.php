<?php

namespace Coleus\Frame\Data;

use Spatie\LaravelData\Data;

class FrameData extends Data
{
    /** @param  list<string>  $sources */
    public function __construct(
        public string $name,
        public ?string $search_query,
        public array $sources,
        public int $interval_seconds,
        public bool $is_active,
    ) {
        //
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    public static function prepareForPipeline(array $properties): array
    {
        $searchQuery = trim((string) ($properties['search_query'] ?? ''));

        return [
            ...$properties,
            'search_query' => filled($searchQuery) ? $searchQuery : null,
            'is_active' => filter_var($properties['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
    }
}
