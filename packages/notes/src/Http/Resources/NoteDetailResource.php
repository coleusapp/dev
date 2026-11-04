<?php

namespace Coleus\Notes\Http\Resources;

use Illuminate\Http\Request;

/**
 * The open note: its list row plus the Markdown the editor loads.
 *
 * @mixin \Coleus\Notes\Models\Note
 */
class NoteDetailResource extends NoteResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'body' => $this->body,
        ];
    }
}
