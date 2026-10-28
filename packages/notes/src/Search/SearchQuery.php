<?php

namespace Coleus\Notes\Search;

use Coleus\Notes\Facades\Notes;
use Coleus\Notes\Markdown\FrontMatter;
use Coleus\Notes\Models\Folder;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Services\FolderService;
use Coleus\Notes\Services\NoteService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * A search box query: plain words (NoteSearch) plus operators.
 *
 *   "exact phrase"           the words together, in title or body
 *   tag:work  tag:#work      tagged work or work/… (like the tag filter)
 *   folder:Trips             in a folder named Trips, or one inside it
 *   task:call                a checklist item mentioning "call"
 *   task-todo:call           …an unticked one (task-todo: alone: any open item)
 *   task-done:call           …a ticked one
 *
 * Operator values can be quoted (folder:"Trip plans"). Everything must
 * match. Task operators narrow in SQL, then check the item lines on the
 * loaded notes (filter()), since a LIKE can't stay on one line.
 */
final class SearchQuery
{
    public const int MAX_OPERATORS = 10;

    private const string TOKEN = '/(?<op>task-todo|task-done|task|tag|folder):(?:"(?<quoted>[^"]*)"|(?<value>[^\s"]*))|"(?<phrase>[^"]+)"/iu';

    private const string TASK_LINE = '/^[ \t]*(?:[-*+]|\d+[.)])[ \t]+\[( |x|X)\][ \t]+(.*)$/mu';

    /**
     * @param  list<string>  $words
     * @param  list<string>  $phrases
     * @param  list<string>  $tags
     * @param  list<string>  $folders
     * @param  list<array{state: 'any'|'todo'|'done', text: string}>  $tasks
     */
    private function __construct(
        public readonly array $words,
        public readonly array $phrases,
        public readonly array $tags,
        public readonly array $folders,
        public readonly array $tasks,
    ) {
        //
    }

    public static function parse(string $terms): self
    {
        $phrases = $tags = $folders = $tasks = [];

        $rest = (string) preg_replace_callback(self::TOKEN, function (array $match) use (&$phrases, &$tags, &$folders, &$tasks): string {
            if (($match['phrase'] ?? '') !== '') {
                $phrases[] = mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $match['phrase'])));

                return ' ';
            }

            $value = trim(($match['quoted'] ?? '') !== '' ? $match['quoted'] : ($match['value'] ?? ''));

            match (mb_strtolower($match['op'])) {
                'tag' => $tags[] = mb_strtolower(ltrim($value, '#')),
                'folder' => $folders[] = $value,
                'task' => $tasks[] = ['state' => 'any', 'text' => mb_strtolower($value)],
                'task-todo' => $tasks[] = ['state' => 'todo', 'text' => mb_strtolower($value)],
                'task-done' => $tasks[] = ['state' => 'done', 'text' => mb_strtolower($value)],
            };

            return ' ';
        }, $terms);

        $keep = fn (array $values): array => array_slice(array_values(array_unique(array_filter($values, fn (string $value) => $value !== ''))), 0, self::MAX_OPERATORS);

        return new self(
            NoteSearch::words($rest),
            $keep($phrases),
            $keep($tags),
            $keep($folders),
            array_slice(array_values(array_unique($tasks, SORT_REGULAR)), 0, self::MAX_OPERATORS),
        );
    }

    /**
     * What to mark in titles and snippets.
     *
     * @return list<string>
     */
    public function highlightTerms(): array
    {
        return array_values(array_unique([
            ...$this->words,
            ...$this->phrases,
            ...array_filter(array_column($this->tasks, 'text'), fn (string $text) => $text !== ''),
        ]));
    }

    /**
     * @param  Builder<Note>  $query
     * @return Builder<Note>
     */
    public function apply(Builder $query): Builder
    {
        NoteSearch::applyWords($query, $this->words);

        foreach ($this->phrases as $phrase) {
            $pattern = '%'.NoteService::escapeLike($phrase).'%';
            $query->where(fn (Builder $either) => $either
                ->whereRaw("lower(title) like ? escape '!'", [$pattern])
                ->orWhereRaw("lower(body) like ? escape '!'", [$pattern]));
        }

        foreach ($this->tags as $tag) {
            Notes::note()->whereTagged($query, $tag);
        }

        foreach ($this->folders as $name) {
            $query->whereIn('folder_id', $this->folderIds($name));
        }

        foreach ($this->tasks as $task) {
            $query->where(function (Builder $marker) use ($task) {
                if ($task['state'] !== 'done') {
                    $marker->orWhere('body', 'like', '%[ ]%');
                }

                if ($task['state'] !== 'todo') {
                    $marker->orWhereRaw("lower(body) like '%[x]%'");
                }
            });

            if ($task['text'] !== '') {
                $query->whereRaw("lower(body) like ? escape '!'", ['%'.NoteService::escapeLike($task['text']).'%']);
            }
        }

        return $query;
    }

    /**
     * Keeps the notes whose checklist items satisfy every task operator.
     *
     * @template TNote of Note
     *
     * @param  Collection<int, TNote>  $notes
     * @return Collection<int, TNote>
     */
    public function filter(Collection $notes): Collection
    {
        if ($this->tasks === []) {
            return $notes;
        }

        return $notes->filter(fn (Note $note) => $this->matchesTasks($note->body))->values();
    }

    /**
     * Best match first: notes are ranked by how often the search terms
     * appear, a title hit counting for ten body hits. The given order
     * breaks ties.
     *
     * @template TNote of Note
     *
     * @param  Collection<int, TNote>  $notes
     * @return Collection<int, TNote>
     */
    public function rank(Collection $notes): Collection
    {
        $terms = $this->highlightTerms();

        if ($terms === []) {
            return $notes;
        }

        $count = fn (string $text): int => array_sum(array_map(fn (string $term) => mb_substr_count(mb_strtolower($text), $term), $terms));

        return $notes
            ->map(fn (Note $note, int $position) => ['note' => $note, 'score' => $count($note->title) * 10 + $count($note->body), 'position' => $position])
            ->sort(fn (array $a, array $b) => [$b['score'], $a['position']] <=> [$a['score'], $b['position']])
            ->pluck('note')
            ->values();
    }

    public function matchesTasks(string $body): bool
    {
        preg_match_all(self::TASK_LINE, FrontMatter::blankOut($body), $items, PREG_SET_ORDER);

        foreach ($this->tasks as $task) {
            $found = false;

            foreach ($items as [, $mark, $text]) {
                $state = $mark === ' ' ? 'todo' : 'done';

                if (($task['state'] === 'any' || $task['state'] === $state) && ($task['text'] === '' || str_contains(mb_strtolower($text), $task['text']))) {
                    $found = true;
                    break;
                }
            }

            if (! $found) {
                return false;
            }
        }

        return true;
    }

    /**
     * Folders with that name (any case) and everything inside them.
     *
     * @return list<int>
     */
    private function folderIds(string $name): array
    {
        $folders = Folder::query()->whereRaw('lower(name) = ?', [mb_strtolower($name)])->get();
        $ids = $folders->flatMap(fn (Folder $folder) => app(FolderService::class)->subtreeIds($folder))->unique()->values()->all();

        return $ids === [] ? [0] : $ids;
    }
}
