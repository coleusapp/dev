<?php

namespace Coleus\Notes\Services;

use Coleus\Notes\Enums\AttachmentKind;
use Coleus\Notes\Jobs\ProcessImageAttachment;
use Coleus\Notes\Models\Attachment;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Storing and serving note attachments (O3.1 / O3.1a).
 *
 * The type is sniffed from the file's content on the server (the
 * browser's claim and the name's ending aren't trusted), must be on the
 * kind's list, and the size within the kind's limit; names ending like
 * pages, scripts or programs are refused outright. Files are stored
 * without their extension under a random name, and served only through
 * the notes route with nosniff, as downloads unless they're images,
 * video, audio or PDF, and sandboxed when downloaded. Images are cleaned
 * up afterwards in the queue (Jobs\ProcessImageAttachment), and smaller
 * copies are served for ?w=.
 */
class Attachments
{
    public function disk(): Filesystem
    {
        return Storage::disk((string) config('notes.attachments.disk'));
    }

    public function store(UploadedFile $file): Attachment
    {
        $name = self::cleanName($file->getClientOriginalName());
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $mime = self::sniff($file);
        $kind = AttachmentKind::forMime($mime);

        if (in_array($extension, AttachmentKind::BLOCKED_EXTENSIONS, true) || $kind === null) {
            throw ValidationException::withMessages(['file' => "Files of this type can't be attached ({$mime})."]);
        }

        if ($file->getSize() > $kind->maxKilobytes() * 1024) {
            throw ValidationException::withMessages(['file' => sprintf('%s files can be up to %s MB.', ucfirst($kind->value), round($kind->maxKilobytes() / 1024))]);
        }

        $uuid = (string) Str::uuid();
        $path = $this->disk()->putFileAs('notes/attachments', $file, $uuid);

        if ($path === false) {
            throw ValidationException::withMessages(['file' => "The file couldn't be stored."]);
        }

        $attachment = Attachment::query()->create([
            'uuid' => $uuid,
            'name' => $name,
            'mime' => $mime,
            'kind' => $kind,
            'size' => (int) $file->getSize(),
            'path' => $path,
        ]);

        if ($kind === AttachmentKind::Image) {
            ProcessImageAttachment::dispatch($attachment->id);
        }

        return $attachment;
    }

    /**
     * The file, with headers that keep the browser from running it. On a
     * local disk the response answers Range requests, so audio and video
     * can seek.
     */
    public function response(Attachment $attachment, ?int $width = null): Response
    {
        $variant = $width === null ? null : ($attachment->variants[(string) $width] ?? null);
        $path = $variant ?? $attachment->path;
        $inline = $attachment->kind->servesInline($attachment->mime);
        $headers = [
            'Content-Type' => $variant === null ? $attachment->mime : 'image/webp',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=31536000, immutable',
            ...($inline ? [] : ['Content-Security-Policy' => 'sandbox']),
        ];
        $disposition = $inline ? 'inline' : 'attachment';
        $disk = $this->disk();

        if (method_exists($disk, 'path') && config('filesystems.disks.'.config('notes.attachments.disk').'.driver') === 'local') {
            return response()->file($disk->path($path), $headers)->setContentDisposition($disposition, $attachment->name, Str::ascii($attachment->name));
        }

        return $disk->response($path, $attachment->name, $headers, $disposition);
    }

    /**
     * The type read from the file's bytes (libmagic), never the browser's
     * Content-Type or the name's ending.
     */
    public static function sniff(UploadedFile $file): string
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file((string) $file->getRealPath());

        return is_string($mime) ? $mime : 'application/octet-stream';
    }

    /**
     * A file name safe to keep and show: no folders, control characters or
     * brackets that would break a Markdown link, at most 200 characters.
     */
    public static function cleanName(string $name): string
    {
        $clean = trim((string) preg_replace(['/[\x00-\x1F\x7F\/\\\\]/u', '/[\[\]]/'], ['', ''], basename($name)));

        return Str::limit($clean === '' ? 'file' : $clean, 200, '');
    }
}
