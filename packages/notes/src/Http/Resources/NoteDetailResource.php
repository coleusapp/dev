<?php

namespace Coleus\Notes\Http\Resources;

use Coleus\Notes\Markdown\FrontMatter;
use Coleus\Notes\Markdown\NoteMarkdown;
use Coleus\Notes\Markdown\WikiLinks\Backlinks;
use Coleus\Notes\Markdown\WikiLinks\WikiLinkResolver;
use Coleus\Notes\Markdown\WikiLinks\WikiLinks;
use Coleus\Notes\Models\Note;
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
        $resolver = WikiLinkResolver::forBody($this->body, $this->id);

        return [
            ...parent::toArray($request),
            'html' => NoteMarkdown::toHtml($this->body, $resolver),
            'tags' => $this->tags()->orderBy('name')->pluck('name')->all(),
            'properties' => FrontMatter::parse($this->body),
            'backlinks' => $this->backlinks(),
            'outgoing' => $this->outgoing($resolver),
            'daily' => $this->daily(),
            'lock' => $this->lock_key_id === null ? null : ['key_id' => $this->lock_key_id, 'iv' => $this->locked_iv, 'ciphertext' => $this->locked_body],
        ];
    }

    /**
     * The notes this one links to (O2.9), in order of first link: each
     * link's title as written and the note it leads to, or null when no
     * note has that name yet.
     *
     * @return list<array{title: string, note: array{id: int, title: string}|null}>
     */
    private function outgoing(WikiLinkResolver $resolver): array
    {
        $titles = WikiLinks::titles($this->body);
        $ids = array_values(array_filter(array_map(fn (string $title) => $resolver->resolve($title), $titles)));
        $notes = $ids === [] ? collect() : Note::query()->whereKey($ids)->pluck('title', 'id');

        return array_map(function (string $title) use ($resolver, $notes): array {
            $id = $resolver->resolve($title);

            return ['title' => $title, 'note' => $id === null ? null : ['id' => $id, 'title' => (string) $notes->get($id, $title)]];
        }, $titles);
    }

    /**
     * For a daily note (O9.1): its day and the nearest daily notes before
     * and after it, for stepping through days (O9.2).
     *
     * @return array{date: string, previous: int|null, next: int|null}|null
     */
    private function daily(): ?array
    {
        if ($this->daily_on === null) {
            return null;
        }

        $date = $this->daily_on->toDateString();

        return [
            'date' => $date,
            'previous' => Note::query()->whereNotNull('daily_on')->whereDate('daily_on', '<', $date)->orderByDesc('daily_on')->value('id'),
            'next' => Note::query()->whereNotNull('daily_on')->whereDate('daily_on', '>', $date)->orderBy('daily_on')->value('id'),
        ];
    }

    /**
     * The notes linking here, each with the lines that link (O2.7).
     *
     * @return list<array{id: int, title: string, updated_at_for_humans: string|null, context: list<list<array{text: string, match: bool}>>}>
     */
    private function backlinks(): array
    {
        $names = Backlinks::names($this->resource);

        return Backlinks::for($this->resource)
            ->map(fn (Note $note) => [
                'id' => $note->id,
                'title' => $note->title,
                'updated_at_for_humans' => $note->updated_at?->diffForHumans(),
                'context' => Backlinks::context($note, $names),
            ])
            ->all();
    }
}
