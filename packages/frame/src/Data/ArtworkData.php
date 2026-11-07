<?php

namespace Coleus\Frame\Data;

use Spatie\LaravelData\Data;

class ArtworkData extends Data
{
    public function __construct(
        public int $source_id,
        public string $title,
        public ?string $artist_display,
        public ?string $date_display,
        public string $image_url,
    ) {
        //
    }
}
