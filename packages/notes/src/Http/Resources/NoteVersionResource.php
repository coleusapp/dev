<?php

namespace Coleus\Notes\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Coleus\Notes\Models\NoteVersion
 */
class NoteVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'title' => $this->title,
            'characters' => mb_strlen($this->body),
            'saved_at' => ($this->updated_at ?? $this->created_at)?->toIso8601String(),
            'saved_at_for_humans' => ($this->updated_at ?? $this->created_at)?->diffForHumans(),
        ];
    }
}
