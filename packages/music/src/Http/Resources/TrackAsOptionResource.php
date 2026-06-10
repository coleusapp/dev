<?php

namespace Coleus\Music\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Coleus\Music\Models\Track
 */
class TrackAsOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'label' => $this->title,
            'value' => $this->id,
        ];
    }
}
