<?php

namespace Coleus\Notes\Http\Resources;

use Coleus\Notes\Markdown\FrontMatter;
use Coleus\Notes\Markdown\NoteMarkdown;
use Coleus\Notes\Markdown\WikiLinks\Backlinks;
use Coleus\Notes\Markdown\WikiLinks\WikiLinkResolver;
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
            'html' => NoteMarkdown::toHtml($this->body, WikiLinkResolver::forBody($this->body, $this->id)),
            'tags' => $this->tags()->orderBy('name')->pluck('name')->all(),
            'properties' => FrontMatter::parse($this->body),
            'backlinks' => Backlinks::for($this->resource)
                ->map(fn ($note) => ['id' => $note->id, 'title' => $note->title, 'updated_at_for_humans' => $note->updated_at?->diffForHumans()])
                ->all(),
        ];
    }
}
