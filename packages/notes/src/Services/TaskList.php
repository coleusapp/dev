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
     * "due" lists open items that have a due date, the groups ordered by
     * their soonest one.
     *
     * @param  array{state?: 'open'|'done'|'all'|'due', tag?: string|null, folder?: int|null}  $filters
     * @return list<array{note: array{id: int, title: string, archived: bool}, items: list<array{line: int, checked: bool, text: string, depth: int, due: string|null}>}>
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
            $items = array_values(array_filter(self::items($note->body), fn (array $item) => match ($state) {
                'all' => true,
                'done' => $item['checked'],
                'due' => ! $item['checked'] && $item['due'] !== null,
                default => ! $item['checked'],
            }));

            if ($items !== []) {
                $groups[] = ['note' => ['id' => $note->id, 'title' => $note->title, 'archived' => $note->archived_at !== null], 'items' => $items];
            }
        }

        if ($state === 'due') {
            usort($groups, fn (array $a, array $b) => min(array_column($a['items'], 'due')) <=> min(array_column($b['items'], 'due')));
        }

        return $groups;
    }

    private static function isDate(string $iso): bool
    {
        [$year, $month, $day] = array_map('intval', explode('-', $iso));

        return checkdate($month, $day, $year);
    }

    /**
     * Each item's due date (O9.8) is its "@YYYY-MM-DD" date chip (B5.4), if
     * it has one; it's taken out of the text.
     *
     * @return list<array{line: int, checked: bool, text: string, depth: int, due: string|null}>
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

            $text = NoteText::plainLine($line);
            $due = preg_match('/(?<![\p{L}\p{N}_.@])@(\d{4}-\d{2}-\d{2})(?![\w-])/u', $text, $date) === 1 && self::isDate($date[1]) ? $date[1] : null;

            $items[] = [
                'line' => $number,
                'checked' => $match[2] !== ' ',
                'text' => $due === null ? $text : trim((string) preg_replace('/\s+/u', ' ', str_replace('@'.$due, '', $text))),
                'depth' => intdiv(strlen(str_replace("\t", '    ', $match[1])), 2),
                'due' => $due,
            ];
        }

        return $items;
    }
}
