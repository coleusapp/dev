<?php

namespace Coleus\Notes\Search;

use Coleus\Notes\Models\Folder;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Services\FolderService;
use Coleus\Notes\Services\NoteService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * The rules a Smart Folder can combine. Every rule that is set must match
 * (AND); within "tags", tags_match chooses all or any.
 *
 *   tags: list<string>, tags_match: 'all'|'any'
 *   folder_id: int|null              (that folder and its sub-folders)
 *   edited_within_days / created_within_days: int|null
 *   edited_from / edited_to / created_from / created_to: Y-m-d|null
 *   pinned: bool                     (only pinned notes)
 *   has_checklist / has_open_tasks: bool
 */
class SmartFolderRules
{
    public const array DEFAULTS = [
        'tags' => [],
        'tags_match' => 'all',
        'folder_id' => null,
        'edited_within_days' => null,
        'created_within_days' => null,
        'edited_from' => null,
        'edited_to' => null,
        'created_from' => null,
        'created_to' => null,
        'pinned' => false,
        'has_checklist' => false,
        'has_open_tasks' => false,
    ];

    /**
     * Checklist markers as they appear in Markdown, for LIKE matching
     * (bulleted and numbered items, ticked or not).
     */
    private const array OPEN_TASK_MARKERS = ['- [ ]', '* [ ]', '+ [ ]', '. [ ]', ') [ ]'];

    private const array DONE_TASK_MARKERS = ['- [x]', '* [x]', '+ [x]', '. [x]', ') [x]'];

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    public static function normalize(array $rules): array
    {
        return array_intersect_key([...self::DEFAULTS, ...$rules], self::DEFAULTS);
    }

    /**
     * @param  Builder<Note>  $query
     * @param  array<string, mixed>  $rules
     * @return Builder<Note>
     */
    public static function apply(Builder $query, array $rules): Builder
    {
        $rules = self::normalize($rules);
        $notes = app(NoteService::class);

        if ($rules['tags'] !== []) {
            if ($rules['tags_match'] === 'any') {
                $query->where(function (Builder $any) use ($rules, $notes) {
                    foreach ($rules['tags'] as $tag) {
                        $any->orWhere(fn (Builder $one) => $notes->whereTagged($one, $tag));
                    }
                });
            } else {
                foreach ($rules['tags'] as $tag) {
                    $notes->whereTagged($query, $tag);
                }
            }
        }

        if ($rules['folder_id'] !== null) {
            $folder = Folder::query()->find($rules['folder_id']);
            $query->whereIn('folder_id', $folder ? app(FolderService::class)->subtreeIds($folder) : [0]);
        }

        foreach (['edited' => 'updated_at', 'created' => 'created_at'] as $rule => $column) {
            if ($rules["{$rule}_within_days"] !== null) {
                $query->where($column, '>=', now()->subDays((int) $rules["{$rule}_within_days"])->startOfDay());
            }
            if ($rules["{$rule}_from"] !== null) {
                $query->where($column, '>=', Carbon::parse($rules["{$rule}_from"])->startOfDay());
            }
            if ($rules["{$rule}_to"] !== null) {
                $query->where($column, '<=', Carbon::parse($rules["{$rule}_to"])->endOfDay());
            }
        }

        if ($rules['pinned']) {
            $query->whereNotNull('pinned_at');
        }

        if ($rules['has_open_tasks']) {
            self::whereBodyContainsAny($query, self::OPEN_TASK_MARKERS);
        } elseif ($rules['has_checklist']) {
            self::whereBodyContainsAny($query, [...self::OPEN_TASK_MARKERS, ...self::DONE_TASK_MARKERS, ...array_map('strtoupper', self::DONE_TASK_MARKERS)]);
        }

        return $query;
    }

    /**
     * @param  Builder<Note>  $query
     * @param  list<string>  $needles
     */
    private static function whereBodyContainsAny(Builder $query, array $needles): void
    {
        $query->where(function (Builder $any) use ($needles) {
            foreach ($needles as $needle) {
                $any->orWhereRaw("body like ? escape '!'", ['%'.NoteService::escapeLike($needle).'%']);
            }
        });
    }
}
