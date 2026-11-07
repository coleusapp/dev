<?php

namespace Coleus\Frame\Support;

use InvalidArgumentException;

/**
 * Fits an image inside a bounding box (keeping its aspect ratio, never
 * upscaling or cropping) and re-encodes it as a JPEG using GD.
 */
class ImageFitter
{
    /**
     * @throws InvalidArgumentException when the bytes are not a decodable image.
     */
    public static function fitAsJpeg(string $bytes, int $maxWidth, int $maxHeight, int $quality = 85): string
    {
        $source = $bytes === '' ? false : @imagecreatefromstring($bytes);

        if ($source === false) {
            throw new InvalidArgumentException('The downloaded file is not a decodable image.');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min($maxWidth / $width, $maxHeight / $height, 1);

        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        imagejpeg($target, null, $quality);

        return (string) ob_get_clean();
    }
}
