<?php

namespace Coleus\Frame\Support;

use InvalidArgumentException;

class ImageFitter
{
    /**
     * What GD needs per pixel of a decoded truecolor image, with headroom.
     */
    public const int BytesPerDecodedPixel = 5;

    /**
     * @throws InvalidArgumentException when the bytes are not a decodable image, or decoding them would exhaust memory.
     */
    public static function fitAsJpeg(string $bytes, int $maxWidth, int $maxHeight, int $quality = 85): string
    {
        $size = $bytes ? @getimagesizefromstring($bytes) : false;

        if (!$size || !$size[0] || !$size[1]) {
            throw new InvalidArgumentException('The downloaded file is not a decodable image.');
        }

        [$width, $height] = $size;
        $scale = min($maxWidth / $width, $maxHeight / $height, 1);
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        if (!self::fitsInMemory($width * $height + $targetWidth * $targetHeight)) {
            throw new InvalidArgumentException("The {$width}x{$height} image is too large to decode in the available memory.");
        }

        $source = @imagecreatefromstring($bytes);

        if (!$source) {
            throw new InvalidArgumentException('The downloaded file is not a decodable image.');
        }

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        imagejpeg($target, null, $quality);

        return (string) ob_get_clean();
    }

    /**
     * Running out of memory is a fatal error, not an exception, so it would
     * kill the whole fetch instead of skipping one image.
     */
    private static function fitsInMemory(int $pixels): bool
    {
        $limit = ini_parse_quantity(ini_get('memory_limit'));

        if ($limit < 0) {
            return true;
        }

        return memory_get_usage() + $pixels * self::BytesPerDecodedPixel < $limit;
    }
}
