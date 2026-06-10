<?php

namespace Coleus\Music\Services;

use Coleus\Music\Data\ArtistData;
use Coleus\Music\Http\Resources\ArtistAsOptionResource;
use Coleus\Music\Models\Artist;
use Coleus\Support\Services\Concerns\CanBeOption;
use Coleus\Support\Services\Service;

class ArtistService extends Service
{
    use CanBeOption;

    protected $model = Artist::class;

    protected $data = ArtistData::class;

    protected string $optionResource = ArtistAsOptionResource::class;
}
