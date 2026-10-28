<?php

namespace Coleus\Notes\Markdown\Attachments;

use League\CommonMark\Util\HtmlElement;

/**
 * Players for attached media (O3.4), picked by the file's ending: video
 * and audio with native controls (loading only metadata until played; the
 * attachment route answers Range requests so they can seek) and PDFs in
 * the browser's own viewer.
 */
class MediaPlayer
{
    public const string VIDEO = '/\.(mp4|webm|mov|m4v|ogv)$/i';

    public const string AUDIO = '/\.(mp3|m4a|aac|wav|ogg|oga|weba|flac)$/i';

    public const string PDF = '/\.pdf$/i';

    /**
     * The player for a file, or null when its ending isn't playable media.
     */
    public static function for(string $url, string $name, ?int $width = null, ?int $height = null): ?HtmlElement
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $size = [...($width ? ['width' => (string) $width] : []), ...($height ? ['height' => (string) $height] : [])];

        return match (true) {
            preg_match(self::VIDEO, $path) === 1 => new HtmlElement('video', ['src' => $url, 'poster' => $path.'?poster=1', 'controls' => '', 'preload' => 'metadata', 'class' => 'attachment-video', 'title' => $name, ...$size], ''),
            preg_match(self::AUDIO, $path) === 1 => new HtmlElement('audio', ['src' => $url, 'controls' => '', 'preload' => 'metadata', 'class' => 'attachment-audio', 'title' => $name], ''),
            preg_match(self::PDF, $path) === 1 => new HtmlElement('iframe', ['src' => $url, 'class' => 'attachment-pdf', 'title' => $name, 'loading' => 'lazy', ...$size], ''),
            default => null,
        };
    }
}
