<?php

namespace Coleus\Notes\Services;

use Coleus\Notes\Markdown\WikiLinks\WikiLinks;
use Coleus\Notes\Models\Attachment;
use Coleus\Notes\Models\Note;
use Illuminate\Support\Collection;

/**
 * Which notes use which attachments (O3.5): a note uses a file when it
 * links to its URL (uploads insert those) or embeds it by name
 * (![[photo.png]]). Notes in the trash count, since restoring them
 * should find their files. Locked notes can't be read, so files used
 * only by them look unused; the manager warns about that.
 */
class AttachmentUsage
{
    /**
     * @param  Collection<int, Attachment>  $attachments
     * @return array<string, list<array{id: int, title: string, trashed: bool}>> by attachment uuid
     */
    public function notesUsing(Collection $attachments): array
    {
        $usage = $attachments->mapWithKeys(fn (Attachment $attachment) => [$attachment->uuid => []])->all();

        if ($attachments->isEmpty()) {
            return $usage;
        }

        $byName = $attachments->groupBy(fn (Attachment $attachment) => mb_strtolower($attachment->name));

        Note::query()->withTrashed()
            ->where(fn ($query) => $query->where('body', 'like', '%/notes/attachments/%')->orWhere('body', 'like', '%![[%'))
            ->get(['id', 'title', 'body', 'deleted_at'])
            ->each(function (Note $note) use ($attachments, $byName, &$usage) {
                $used = [];

                foreach ($attachments as $attachment) {
                    if (str_contains($note->body, '/notes/attachments/'.$attachment->uuid)) {
                        $used[$attachment->uuid] = true;
                    }
                }

                preg_match_all('/!\[\[([^\[\]\n|]{1,255})(?:\|[^\]\n]*)?\]\]/u', $note->body, $embeds);

                foreach ($embeds[1] as $name) {
                    // A name points at the newest attachment called that (as rendering does).
                    $newest = $byName->get(mb_strtolower(WikiLinks::normalizeTitle($name)))?->sortByDesc('id')->first();

                    if ($newest) {
                        $used[$newest->uuid] = true;
                    }
                }

                foreach (array_keys($used) as $uuid) {
                    $usage[$uuid][] = ['id' => $note->id, 'title' => $note->title, 'trashed' => $note->trashed()];
                }
            });

        return $usage;
    }

    /**
     * Removes the file, its copies and its poster, then the record.
     */
    public function delete(Attachment $attachment): void
    {
        $disk = app(Attachments::class)->disk();
        $disk->delete(array_filter([$attachment->path, $attachment->poster_path, ...array_values($attachment->variants ?? [])]));
        $attachment->delete();
    }
}
