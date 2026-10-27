<?php

namespace Coleus\Notes\Data;

use Spatie\LaravelData\Data;

class NoteData extends Data
{
    public function __construct(
        public string $body = '',
    ) {
        //
    }
}
