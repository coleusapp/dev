<?php

namespace Coleus\Notes\Enums;

/**
 * What an uploaded file is, decided from its sniffed MIME type (O3.1a).
 * Each kind has its own size limit and allowed types; anything not listed
 * is refused.
 */
enum AttachmentKind: string
{
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case Document = 'document';

    /**
     * @var array<string, list<string>>
     */
    public const array MIME_TYPES = [
        'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif', 'image/heic', 'image/heif'],
        'video' => ['video/mp4', 'video/webm', 'video/quicktime', 'video/ogg'],
        'audio' => ['audio/mpeg', 'audio/mp4', 'audio/x-m4a', 'audio/aac', 'audio/ogg', 'audio/wav', 'audio/x-wav', 'audio/webm', 'audio/flac', 'audio/x-flac'],
        'document' => [
            'application/pdf', 'text/plain', 'text/markdown', 'text/csv', 'application/json', 'application/rtf', 'text/rtf', 'application/zip',
            'application/msword', 'application/vnd.ms-excel', 'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.oasis.opendocument.text', 'application/vnd.oasis.opendocument.spreadsheet', 'application/vnd.oasis.opendocument.presentation',
            'image/svg+xml',
        ],
    ];

    /**
     * File name endings refused whatever their content looks like: pages,
     * scripts and programs a browser or OS might run.
     */
    public const array BLOCKED_EXTENSIONS = [
        'html', 'htm', 'xhtml', 'shtml', 'js', 'mjs', 'php', 'phtml', 'phar', 'exe', 'msi', 'bat', 'cmd', 'com', 'scr', 'ps1', 'sh', 'bash', 'zsh',
        'app', 'dmg', 'pkg', 'jar', 'vbs', 'wsf', 'hta', 'cpl', 'dll', 'so', 'dylib', 'apk',
    ];

    public static function forMime(string $mime): ?self
    {
        foreach (self::MIME_TYPES as $kind => $types) {
            if (in_array($mime, $types, true)) {
                return self::from($kind);
            }
        }

        return null;
    }

    public function maxKilobytes(): int
    {
        return (int) config('notes.attachments.max_kb.'.$this->value);
    }

    /**
     * Shown in the browser (img, video, audio, PDF viewer) rather than
     * downloaded. Other documents, SVG included, are always downloads.
     */
    public function servesInline(string $mime): bool
    {
        return $this !== self::Document || $mime === 'application/pdf';
    }
}
