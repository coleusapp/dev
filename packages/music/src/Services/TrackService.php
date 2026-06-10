<?php

namespace Coleus\Music\Services;

use Coleus\Music\Data\TrackData;
use Coleus\Music\Http\Resources\TrackAsOptionResource;
use Coleus\Music\Models\Track;
use Coleus\Support\Services\Concerns\CanBeOption;
use Coleus\Support\Services\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TrackService extends Service
{
    use CanBeOption;

    protected $model = Track::class;

    protected $data = TrackData::class;

    protected string $optionResource = TrackAsOptionResource::class;

    public function defaultQuery(): Builder
    {
        return $this->model::query()->with('artist', 'album', 'genre')->orderBy('created_at', 'desc');
    }

    public function save(mixed $payload, ?Track $model = null): Track
    {
        $data = TrackData::from($payload)->toArray();

        if ($payload instanceof Request && $payload->hasFile('file')) {
            if ($model?->path) {
                Storage::delete($model->path);
            }

            $data['path'] = $payload->file('file')->store('music');
        }

        if ($model) {
            $model->update($data);

            return $model;
        }

        return Track::create($data);
    }
}
