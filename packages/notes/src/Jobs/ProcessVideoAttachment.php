<?php

namespace Coleus\Notes\Jobs;

use Coleus\Notes\Enums\AttachmentKind;
use Coleus\Notes\Models\Attachment;
use Coleus\Notes\Services\Attachments;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Process;
use Spatie\Multitenancy\Jobs\NotTenantAware;

/**
 * Reads an uploaded video's duration and size with ffprobe and grabs a
 * poster frame with ffmpeg (O3.1d), shown before the video plays. Without
 * FFmpeg, or for a file it can't read, the video is left as it is and
 * marked processed.
 *
 * Not tenant-aware, like ProcessImageAttachment.
 */
class ProcessVideoAttachment implements NotTenantAware, ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public function __construct(public readonly int $attachmentId)
    {
        //
    }

    public function handle(Attachments $attachments): void
    {
        $attachment = Attachment::query()->withoutGlobalScopes()->find($this->attachmentId);

        if (! $attachment || $attachment->kind !== AttachmentKind::Video) {
            return;
        }

        $disk = $attachments->disk();
        $local = tempnam(sys_get_temp_dir(), 'notes-video-');
        $poster = $local.'.jpg';

        try {
            file_put_contents($local, $disk->readStream($attachment->path));
            $facts = self::probe($local);
            $changes = ['processed_at' => now()];

            if ($facts !== null) {
                $changes = [...$changes, 'width' => $facts['width'], 'height' => $facts['height'], 'duration_ms' => $facts['duration_ms']];
                $at = $facts['duration_ms'] > 2000 ? '1' : '0';
                $grab = Process::timeout(120)->run([(string) config('notes.attachments.ffmpeg'), '-v', 'error', '-y', '-ss', $at, '-i', $local, '-frames:v', '1', '-vf', 'scale=min(1280\,iw):-2', '-q:v', '4', $poster]);

                if ($grab->successful() && is_file($poster) && filesize($poster) > 0) {
                    $disk->put($attachment->path.'-poster.jpg', (string) file_get_contents($poster));
                    $changes['poster_path'] = $attachment->path.'-poster.jpg';
                }
            }

            $attachment->forceFill($changes)->save();
        } finally {
            @unlink($local);
            @unlink($poster);
        }
    }

    /**
     * @return array{width: int|null, height: int|null, duration_ms: int|null}|null
     */
    private static function probe(string $file): ?array
    {
        $result = Process::timeout(60)->run([(string) config('notes.attachments.ffprobe'), '-v', 'error', '-select_streams', 'v:0', '-show_entries', 'stream=width,height:format=duration', '-of', 'json', $file]);
        $json = $result->successful() ? json_decode($result->output(), true) : null;

        if (! is_array($json)) {
            return null;
        }

        $stream = $json['streams'][0] ?? [];
        $duration = $json['format']['duration'] ?? null;

        return [
            'width' => isset($stream['width']) ? (int) $stream['width'] : null,
            'height' => isset($stream['height']) ? (int) $stream['height'] : null,
            'duration_ms' => is_numeric($duration) ? (int) round((float) $duration * 1000) : null,
        ];
    }
}
