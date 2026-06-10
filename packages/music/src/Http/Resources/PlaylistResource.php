<?php

namespace Coleus\Music\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Coleus\Music\Models\Playlist
 */
class PlaylistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'tracks' => PlaylistTrackResource::collection($this->whenLoaded('tracks')),
        ];
    }
}
