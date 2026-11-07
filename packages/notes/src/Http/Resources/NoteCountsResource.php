<?php

namespace Coleus\Notes\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property array{notes: int, trash: int} $resource */
class NoteCountsResource extends JsonResource
{
    /** @return array{notes: int, trash: int} */
    public function toArray(Request $request): array
    {
        return [
            'notes' => $this->resource['notes'],
            'trash' => $this->resource['trash'],
        ];
    }
}
