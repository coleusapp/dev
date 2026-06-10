<?php

namespace Coleus\Music\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Coleus\Music\Models\Album
 */
class AlbumResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'artist_id' => $this->artist_id,
            'artist' => $this->whenLoaded('artist', fn () => $this->artist->name),
            'release_date' => $this->release_date,
        ];
    }
}
