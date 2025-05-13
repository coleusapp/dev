<?php

namespace App\Extensions\Health\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Extensions\Health\Models\Category
 */
class MuscleGroupAsOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'label' => $this->name,
            'value' => $this->id,
        ];
    }
}
