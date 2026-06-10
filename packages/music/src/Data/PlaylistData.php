<?php

namespace Coleus\Music\Data;

use Spatie\LaravelData\Data;

class PlaylistData extends Data
{
    public function __construct(
        public ?int $id,
        public string $name,
        public ?string $description,
    ) {
        //
    }
}
