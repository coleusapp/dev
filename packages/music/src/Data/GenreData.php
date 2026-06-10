<?php

namespace Coleus\Music\Data;

use Spatie\LaravelData\Data;

class GenreData extends Data
{
    public function __construct(
        public string $name,
    ) {
        //
    }
}
