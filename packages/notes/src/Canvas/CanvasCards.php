<?php

namespace Coleus\Notes\Canvas;

use Coleus\Notes\Models\Attachment;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Support\NoteText;

/**
 * What a canvas's note and attachment cards show (O10.1), looked up in
 * two queries: a note's title, snippet and lock; an attachment's name,
 * kind and URL. Cards pointing at something gone (deleted, or another
 * user's) are left out, and the canvas shows them as missing.
 *
 * @phpstan-import-type Data from JsonCanvas
 */
class CanvasCards
{
    /**
     * @param  Data  $data
     * @return array{notes: array<int, array{title: string, snippet: string, locked: bool}>, attachments: array<string, array{name: string, kind: string, url: string}>}
     */
    public static function for(array $data): array
    {
        $noteIds = [];
        $uuids = [];

        foreach ($data['nodes'] as $node) {
            if (isset($node['note'])) {
                $noteIds[] = (int) $node['note'];
            } elseif (isset($node['attachment'])) {
                $uuids[] = (string) $node['attachment'];
            }
        }

        $notes = $noteIds === [] ? collect() : Note::query()->whereKey(array_unique($noteIds))->get(['id', 'title', 'body', 'lock_key_id']);
        $attachments = $uuids === [] ? collect() : Attachment::query()->whereIn('uuid', array_unique($uuids))->get();

        return [
            'notes' => $notes->mapWithKeys(fn (Note $note) => [$note->id => [
                'title' => $note->title,
                'snippet' => $note->isLocked() ? '' : NoteText::snippet($note->body, 400),
                'locked' => $note->isLocked(),
            ]])->all(),
            'attachments' => $attachments->mapWithKeys(fn (Attachment $attachment) => [$attachment->uuid => [
                'name' => $attachment->name,
                'kind' => $attachment->kind->value,
                'url' => $attachment->url(),
            ]])->all(),
        ];
    }
}
