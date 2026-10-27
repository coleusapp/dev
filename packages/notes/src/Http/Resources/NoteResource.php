<?php

namespace Coleus\Notes\Http\Resources;

use Coleus\Notes\Support\NoteText;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Coleus\Notes\Models\Note
 */
class NoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'snippet' => NoteText::snippet($this->body),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'updated_at_for_humans' => $this->updated_at?->diffForHumans(),
        ];
    }
}
