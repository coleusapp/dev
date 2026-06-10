<?php

namespace Coleus\Music\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Coleus\Music\Models\Track
 */
class TrackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'artist_id' => $this->artist_id,
            'artist' => $this->whenLoaded('artist', fn () => $this->artist->name),
            'album_id' => $this->album_id,
            'album' => $this->whenLoaded('album', fn () => $this->album?->title),
            'genre_id' => $this->genre_id,
            'genre' => $this->whenLoaded('genre', fn () => $this->genre?->name),
            'duration' => $this->duration,
            'track_number' => $this->track_number,
            'path' => $this->path,
        ];
    }
}
