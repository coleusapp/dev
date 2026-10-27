<?php

namespace Coleus\Notes\Http\Resources;

use Coleus\Notes\Markdown\NoteMarkdown;
use Illuminate\Http\Request;

/**
 * The open note: everything the list row has, plus the rendered preview.
 * Kept out of the list so rendering only happens for one note per request.
 *
 * @mixin \Coleus\Notes\Models\Note
 */
class NoteDetailResource extends NoteResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'html' => NoteMarkdown::toHtml($this->body),
            'tags' => $this->tags()->orderBy('name')->pluck('name')->all(),
        ];
    }
}
