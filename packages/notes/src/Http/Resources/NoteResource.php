<?php

namespace Coleus\Notes\Http\Resources;

use Coleus\Notes\Search\Highlighter;
use Coleus\Notes\Support\NoteText;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Coleus\Notes\Models\Note
 */
class NoteResource extends JsonResource
{
    /**
     * @var list<string>
     */
    protected array $highlightWords = [];

    /**
     * Mark these search words in the title and snippet.
     *
     * @param  list<string>  $words
     */
    public function highlight(array $words): static
    {
        $this->highlightWords = $words;

        return $this;
    }

    public function toArray(Request $request): array
    {
        $snippet = $this->highlightWords === []
            ? NoteText::snippet($this->body)
            : NoteText::searchSnippet($this->body, $this->highlightWords);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'snippet' => $snippet,
            'highlights' => $this->highlightWords === [] ? null : [
                'title' => Highlighter::parts($this->title, $this->highlightWords),
                'snippet' => Highlighter::parts($snippet, $this->highlightWords),
            ],
            'pinned' => $this->pinned_at !== null,
            'archived' => $this->archived_at !== null,
            'trashed' => $this->deleted_at !== null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'updated_at_for_humans' => $this->updated_at?->diffForHumans(),
        ];
    }
}
