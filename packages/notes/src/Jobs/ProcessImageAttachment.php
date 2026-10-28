<?php

namespace Coleus\Notes\Jobs;

use Coleus\Notes\Enums\AttachmentKind;
use Coleus\Notes\Models\Attachment;
use Coleus\Notes\Services\Attachments;
use GdImage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Spatie\Multitenancy\Jobs\NotTenantAware;

/**
 * Cleans an uploaded image (O3.1c):
 * - turned upright by its EXIF orientation,
 * - re-encoded, which leaves all metadata (camera, GPS…) behind,
 * - shrunk to fit max_dimension, compressed (JPEG 85, WebP 82, PNG
 *   lossless),
 * - copied as WebP at each configured width smaller than itself, for
 *   srcset.
 *
 * Needs PHP's gd extension (and exif for orientation). Without them, or
 * for types gd can't read (HEIC, AVIF) and animated-capable GIFs, the
 * original is kept as uploaded.
 *
 * Not tenant-aware: it works on one attachment by id and needs no tenant
 * (the host has no tenant finder, so a tenant-aware job could never run).
 */
class ProcessImageAttachment implements NotTenantAware, ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $attachmentId)
    {
        //
    }

    public function handle(Attachments $attachments): void
    {
        $attachment = Attachment::query()->withoutGlobalScopes()->find($this->attachmentId);

        if (! $attachment || $attachment->kind !== AttachmentKind::Image || ! function_exists('imagecreatefromstring')) {
            return;
        }

        $disk = $attachments->disk();
        $bytes = $disk->get($attachment->path);
        $format = match ($attachment->mime) {
            'image/jpeg' => 'jpeg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => null,
        };
        $image = $format === null || $bytes === null ? false : @imagecreatefromstring($bytes);

        if ($image === false) {
            $attachment->forceFill(['processed_at' => now()])->save();

            return;
        }

        $image = self::upright($image, $format === 'jpeg' ? self::orientation($bytes) : 1);
        $image = self::fit($image, (int) config('notes.attachments.max_dimension'));
        $disk->put($attachment->path, self::encode($image, $format));

        $variants = [];

        foreach ((array) config('notes.attachments.image_widths') as $width) {
            if ($width < imagesx($image)) {
                $path = $attachment->path.'-'.$width.'.webp';
                $disk->put($path, self::encode(self::fit($image, (int) $width, widthOnly: true), 'webp'));
                $variants[(string) $width] = $path;
            }
        }

        $attachment->forceFill([
            'width' => imagesx($image),
            'height' => imagesy($image),
            'size' => (int) $disk->size($attachment->path),
            'variants' => $variants,
            'processed_at' => now(),
        ])->save();
    }

    private static function orientation(string $bytes): int
    {
        if (! function_exists('exif_read_data')) {
            return 1;
        }

        $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($bytes));

        return is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
    }

    /**
     * Applies an EXIF orientation (1–8) so the pixels are the right way up.
     */
    private static function upright(GdImage $image, int $orientation): GdImage
    {
        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }

        $angle = match ($orientation) {
            3, 4 => 180,
            5, 6 => 270,
            7, 8 => 90,
            default => 0,
        };

        return $angle === 0 ? $image : (imagerotate($image, $angle, 0) ?: $image);
    }

    /**
     * Scales down (never up) so the longest side, or the width, is at most $limit.
     */
    private static function fit(GdImage $image, int $limit, bool $widthOnly = false): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $longest = $widthOnly ? $width : max($width, $height);

        if ($limit <= 0 || $longest <= $limit) {
            return $image;
        }

        $scale = $limit / $longest;
        $resized = imagescale($image, max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)), IMG_BICUBIC);

        return $resized ?: $image;
    }

    private static function encode(GdImage $image, string $format): string
    {
        ob_start();

        match ($format) {
            'jpeg' => imagejpeg($image, null, 85),
            'png' => (imagesavealpha($image, true) && imagepng($image, null, 6)),
            default => (imagesavealpha($image, true) && imagewebp($image, null, 82)),
        };

        return (string) ob_get_clean();
    }
}
