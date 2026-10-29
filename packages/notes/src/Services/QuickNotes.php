<?php

namespace Coleus\Notes\Services;

use Coleus\Notes\Models\Folder;
use Coleus\Notes\Models\Note;

/**
 * Quick Notes (A9.1 / A9.2): jotted down from anywhere, kept in a
 * top-level "Quick Notes" folder. Started from another Coleus page, the
 * note ends with a link back to it.
 */
class QuickNotes
{
    public const string FOLDER = 'Quick Notes';

    public function __construct(private readonly NoteService $notes, private readonly FolderService $folders)
    {
        //
    }

    public function create(string $body, ?string $from = null, ?string $fromTitle = null): Note
    {
        $from = self::safePath($from);

        if ($from !== null) {
            $label = str_replace(['[', ']'], ['(', ')'], trim((string) $fromTitle) !== '' ? trim((string) $fromTitle) : $from);
            $body = rtrim($body)."\n\nFrom [{$label}](".str_replace([' ', '(', ')'], ['%20', '%28', '%29'], $from).")\n";
        }

        $note = $this->notes->store(['body' => $body]);
        $this->folders->moveNote($note, $this->folder());

        return $note->refresh();
    }

    /**
     * Only a path on this site ("/calendar/3"), never another site or a
     * scheme, so a link back can't lead anywhere unexpected.
     */
    public static function safePath(?string $path): ?string
    {
        $path = trim((string) $path);

        return $path !== '' && str_starts_with($path, '/') && ! str_starts_with($path, '//') && ! str_contains($path, '\\') && preg_match('/[\x00-\x1F]/', $path) !== 1
            ? $path
            : null;
    }

    private function folder(): Folder
    {
        return Folder::query()->whereNull('parent_id')->whereRaw('lower(name) = ?', [mb_strtolower(self::FOLDER)])->first()
            ?? $this->folders->create(self::FOLDER, null);
    }
}
