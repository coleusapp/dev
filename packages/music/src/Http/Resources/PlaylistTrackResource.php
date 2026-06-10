<?php

namespace Coleus\Music\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaylistTrackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'playlist_id' => $this->pivot->playlist_id,
            'track_id' => $this->pivot->track_id,
        ];
    }
}
