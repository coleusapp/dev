<?php

namespace Coleus\Music\Data;

use Spatie\LaravelData\Data;

class AlbumData extends Data
{
    public function __construct(
        public ?int $id,
        public string $title,
        public int $artist_id,
        public ?string $release_date,
    ) {
        //
    }
}
