<?php

namespace Coleus\Notes\Services;

use Coleus\Notes\Enums\AttachmentKind;
use Coleus\Notes\Models\Attachment;
use Coleus\Notes\Models\Note;
use Illuminate\Support\Collection;

/**
 * The picture a note shows in the gallery (A1.4): its first attached image
 * (linked by URL, as uploads insert it, or embedded by name), as the small
 * copy. Notes without one show their text instead. Names are looked up in
 * one query for the whole list.
 */
class NoteThumbnails
{
    private const string IMAGE_URL = '#/notes/attachments/([0-9a-f-]{36})/[^)\s"]+\.(?:png|jpe?g|gif|webp|avif|heic|heif)#i';

    private const string IMAGE_EMBED = '/!\[\[([^\[\]\n|]{1,255}\.(?:png|jpe?g|gif|webp|avif|heic|heif))(?:\|[^\]\n]*)?\]\]/iu';

    /**
     * @param  Collection<int, Note>  $notes
     * @return array<int, string> thumbnail URL by note id
     */
    public function for(Collection $notes): array
    {
        $first = [];
        $names = [];

        foreach ($notes as $note) {
            $url = preg_match(self::IMAGE_URL, $note->body, $match, PREG_OFFSET_CAPTURE) === 1 ? ['uuid', $match[1][0], $match[0][1]] : null;
            $embed = preg_match(self::IMAGE_EMBED, $note->body, $named, PREG_OFFSET_CAPTURE) === 1 ? ['name', mb_strtolower(trim($named[1][0])), $named[0][1]] : null;
            $found = collect([$url, $embed])->filter()->sortBy(2)->first();

            if ($found !== null) {
                $first[$note->id] = $found;

                if ($found[0] === 'name') {
                    $names[$found[1]] = true;
                }
            }
        }

        $byName = $names === [] ? collect() : Attachment::query()
            ->where('kind', AttachmentKind::Image)
            ->whereIn(\Illuminate\Support\Facades\DB::raw('lower(name)'), array_keys($names))
            ->orderBy('id')
            ->get()
            ->keyBy(fn (Attachment $attachment) => mb_strtolower($attachment->name));

        $thumbnails = [];

        foreach ($first as $id => [$type, $value]) {
            $attachment = $type === 'name' ? $byName->get($value) : null;
            $base = $type === 'uuid' ? self::urlFor($value) : ($attachment ? $attachment->url() : null);

            if ($base !== null) {
                $thumbnails[$id] = $base.'?w=320';
            }
        }

        return $thumbnails;
    }

    /**
     * The attachment route for a uuid; the name part is only for reading,
     * so any will do.
     */
    private static function urlFor(string $uuid): string
    {
        return route('notes.attachments.show', ['attachment' => $uuid, 'name' => 'image'], false);
    }
}
