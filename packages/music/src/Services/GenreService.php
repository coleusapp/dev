<?php

namespace Coleus\Music\Services;

use Coleus\Music\Data\GenreData;
use Coleus\Music\Http\Resources\GenreAsOptionResource;
use Coleus\Music\Models\Genre;
use Coleus\Support\Services\Concerns\CanBeOption;
use Coleus\Support\Services\Service;

class GenreService extends Service
{
    use CanBeOption;

    protected $model = Genre::class;

    protected $data = GenreData::class;

    protected string $optionResource = GenreAsOptionResource::class;
}
