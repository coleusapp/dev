<?php

namespace Coleus\Music\Data;

use Spatie\LaravelData\Data;

class TrackData extends Data
{
    public function __construct(
        public ?int $id,
        public string $title,
        public int $artist_id,
        public ?int $album_id,
        public ?int $genre_id,
        public ?int $duration,
        public ?int $track_number,
    ) {
        //
    }
}
