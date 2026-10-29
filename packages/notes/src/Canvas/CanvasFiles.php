<?php

namespace Coleus\Notes\Canvas;

use Coleus\Notes\Markdown\Attachments\AttachmentEmbedExtension;
use Coleus\Notes\Markdown\WikiLinks\WikiLinkResolver;
use Coleus\Notes\Models\Attachment;
use Coleus\Notes\Models\Canvas;
use Illuminate\Support\Facades\DB;

/**
 * .canvas files in and out (O10.2), so boards move to and from Obsidian.
 *
 * Out: plain JSON Canvas. Note cards name their note as "Title.md" and
 * attachment cards the file's name; the ids kept here are left out.
 * In: file cards are matched by name, folders ignored: "….md" (or no
 * ending) to a note by title or alias, any case; a file name to the
 * newest attachment with that name. Unmatched cards stay, showing as
 * missing with the path they named.
 *
 * @phpstan-import-type Data from JsonCanvas
 */
class CanvasFiles
{
    public const int MAX_KILOBYTES = 5120;

    /**
     * @return Data
     */
    public function export(Canvas $canvas): array
    {
        $data = JsonCanvas::normalize($canvas->data);
        $cards = CanvasCards::for($data);

        $data['nodes'] = array_map(function (array $node) use ($cards): array {
            if ($node['type'] !== 'file') {
                return $node;
            }

            $file = match (true) {
                isset($node['note']) => isset($cards['notes'][$node['note']]) ? self::fileName($cards['notes'][$node['note']]['title']).'.md' : $node['file'],
                isset($node['attachment']) => $cards['attachments'][$node['attachment']]['name'] ?? $node['file'],
                default => $node['file'],
            };
            unset($node['note'], $node['attachment']);

            return [...$node, 'file' => $file !== '' ? $file : 'Untitled.md'];
        }, $data['nodes']);

        return $data;
    }

    /**
     * @param  array<mixed>  $json  a decoded .canvas file
     */
    public function import(array $json, string $name): Canvas
    {
        $data = JsonCanvas::normalize($json);
        $paths = array_values(array_filter(array_map(fn (array $node) => $node['type'] === 'file' && ! isset($node['note']) && ! isset($node['attachment']) ? $node['file'] : null, $data['nodes'])));

        $titles = array_values(array_unique(array_filter(array_map(
            fn (string $path) => AttachmentEmbedExtension::isFileName($path) ? null : self::titleOf($path),
            $paths,
        ), fn (?string $title) => $title !== null && $title !== '' && preg_match('/[\[\]|#\n]/', $title) !== 1)));
        $notes = $titles === [] ? null : WikiLinkResolver::forBody(implode(' ', array_map(fn (string $title) => "[[{$title}]]", $titles)));

        $fileNames = array_values(array_unique(array_map(fn (string $path) => mb_strtolower(basename($path)), array_filter($paths, AttachmentEmbedExtension::isFileName(...)))));
        $attachments = $fileNames === [] ? collect() : Attachment::query()
            ->whereIn(DB::raw('lower(name)'), $fileNames)
            ->orderBy('id')
            ->get()
            ->keyBy(fn (Attachment $attachment) => mb_strtolower($attachment->name));

        $data['nodes'] = array_map(function (array $node) use ($notes, $attachments): array {
            if ($node['type'] !== 'file' || isset($node['note']) || isset($node['attachment'])) {
                return $node;
            }

            if (AttachmentEmbedExtension::isFileName($node['file'])) {
                $attachment = $attachments->get(mb_strtolower(basename($node['file'])));

                return $attachment ? [...$node, 'attachment' => $attachment->uuid] : $node;
            }

            $noteId = $notes?->resolve(self::titleOf($node['file']));

            return $noteId !== null ? [...$node, 'note' => $noteId] : $node;
        }, $data['nodes']);

        return Canvas::query()->create(['name' => mb_substr(trim($name) !== '' ? trim($name) : 'Imported canvas', 0, 100), 'data' => JsonCanvas::normalize($data)]);
    }

    /**
     * "Folder/Trip plan.md" → "Trip plan".
     */
    public static function titleOf(string $path): string
    {
        return trim((string) preg_replace('/\.md$/i', '', basename(str_replace('\\', '/', $path))));
    }

    /**
     * A note title as a file name: no path separators or characters
     * Windows and macOS refuse.
     */
    public static function fileName(string $title): string
    {
        $clean = trim((string) preg_replace(['/[\/\\\\:*?"<>|\x00-\x1F]+/u', '/\s+/u'], ' ', $title));

        return $clean !== '' ? mb_substr($clean, 0, 150) : 'Untitled';
    }
}
