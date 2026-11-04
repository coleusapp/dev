<?php

namespace Coleus\Notes\Http\Resources;

use Coleus\Notes\Support\NoteText;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A row in the note list.
 *
 * @mixin \Coleus\Notes\Models\Note
 */
class NoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'snippet' => NoteText::snippet($this->body),
            'pinned' => $this->pinned_at !== null,
            'archived' => $this->archived_at !== null,
            'bookmarked' => $this->bookmarked_at !== null,
            'trashed' => $this->deleted_at !== null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'updated_at_for_humans' => $this->updated_at?->diffForHumans(),
        ];
    }
}
