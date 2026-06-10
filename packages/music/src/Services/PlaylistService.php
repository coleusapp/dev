<?php

namespace Coleus\Music\Services;

use Coleus\Music\Data\PlaylistData;
use Coleus\Music\Models\Playlist;
use Coleus\Support\Services\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PlaylistService extends Service
{
    protected $model = Playlist::class;

    protected $data = PlaylistData::class;

    protected static function save(array $payload, ?Model $model = null): Playlist
    {
        if ($model) {
            $model->update($payload);
        } else {
            $model = Playlist::create($payload);
        }

        DB::transaction(function () use ($model, $payload) {
            $model->tracks()->detach();
            collect($payload['tracks'] ?? [])
                ->each(function ($track) use ($model) {
                    $model->tracks()->attach($track['track_id']);
                });
        });

        return $model;
    }
}
