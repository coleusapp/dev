<?php

namespace Coleus\Notes\Services;

use Coleus\Notes\Markdown\Checklist;
use Coleus\Notes\Markdown\FrontMatter;
use Coleus\Notes\Models\Folder;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Support\NoteText;
use Illuminate\Database\Eloquent\Builder;

/**
 * Every checklist item across the user's notes (O9.6), read with the
 * Markdown parser so look-alikes in code don't count. Trashed and locked
 * notes are left out; archived ones are in, marked.
 */
class TaskList
{
    public function __construct(private readonly NoteService $notes, private readonly FolderService $folders)
    {
        //
    }

    /**
     * @param  array{state?: 'open'|'done'|'all', tag?: string|null, folder?: int|null}  $filters
     * @return list<array{note: array{id: int, title: string, archived: bool}, items: list<array{line: int, checked: bool, text: string, depth: int}>}>
     */
    public function grouped(array $filters = []): array
    {
        $state = $filters['state'] ?? 'open';
        $query = Note::query()
            ->whereNull('lock_key_id')
            ->where(fn (Builder $marker) => $marker->where('body', 'like', '%[ ]%')->orWhereRaw("lower(body) like '%[x]%'"))
            ->orderByDesc('updated_at');

        if (($filters['tag'] ?? null) !== null) {
            $this->notes->whereTagged($query, (string) $filters['tag']);
        }

        if (($filters['folder'] ?? null) !== null) {
            $folder = Folder::query()->find($filters['folder']);
            $query->whereIn('folder_id', $folder ? $this->folders->subtreeIds($folder) : [0]);
        }

        $groups = [];

        foreach ($query->get() as $note) {
            $items = array_values(array_filter(self::items($note->body), fn (array $item) => $state === 'all' || $item['checked'] === ($state === 'done')));

            if ($items !== []) {
                $groups[] = ['note' => ['id' => $note->id, 'title' => $note->title, 'archived' => $note->archived_at !== null], 'items' => $items];
            }
        }

        return $groups;
    }

    /**
     * @return list<array{line: int, checked: bool, text: string, depth: int}>
     */
    public static function items(string $body): array
    {
        $lines = preg_split('/\R/', FrontMatter::blankOut($body)) ?: [];
        $items = [];

        foreach (Checklist::taskLines($body) as $number) {
            $line = $lines[$number - 1] ?? '';

            if (preg_match('/^([ \t]*)(?:[-*+]|\d+[.)])[ \t]+\[( |x|X)\]/', $line, $match) !== 1) {
                continue;
            }

            $items[] = [
                'line' => $number,
                'checked' => $match[2] !== ' ',
                'text' => NoteText::plainLine($line),
                'depth' => intdiv(strlen(str_replace("\t", '    ', $match[1])), 2),
            ];
        }

        return $items;
    }
}
