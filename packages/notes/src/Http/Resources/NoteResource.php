<?php

namespace Coleus\Notes\Http\Resources;

use Coleus\Notes\Models\Note;
use Coleus\Notes\Support\NoteText;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Note */
class NoteResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'snippet' => NoteText::snippet($this->body),
            'pinned' => (bool) $this->pinned_at,
            'trashed' => (bool) $this->deleted_at,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'updated_at_for_humans' => $this->updated_at?->diffForHumans(),
        ];
    }
}
