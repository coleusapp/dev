<?php

namespace Coleus\Music\Services;

use Coleus\Music\Data\AlbumData;
use Coleus\Music\Http\Resources\AlbumAsOptionResource;
use Coleus\Music\Models\Album;
use Coleus\Support\Services\Concerns\CanBeOption;
use Coleus\Support\Services\Service;
use Illuminate\Database\Eloquent\Builder;

class AlbumService extends Service
{
    use CanBeOption;

    protected $model = Album::class;

    protected $data = AlbumData::class;

    protected string $optionResource = AlbumAsOptionResource::class;

    public function defaultQuery(): Builder
    {
        return $this->model::query()->with('artist')->orderBy('created_at', 'desc');
    }
}
