<?php

namespace Coleus\Notes\Services;

use Coleus\Notes\Models\Folder;
use Coleus\Notes\Models\Note;
use Illuminate\Database\Eloquent\Builder;

class FolderService
{
    public function create(string $name, ?Folder $parent): Folder
    {
        $folder = new Folder(['name' => $name]);
        $folder->parent()->associate($parent);
        $folder->save();

        return $folder;
    }

    /**
     * Puts a note in a folder (or at the top level with null) without
     * counting as an edit.
     */
    public function moveNote(Note $note, ?Folder $folder): void
    {
        Note::withoutTimestamps(fn () => $note->forceFill(['folder_id' => $folder?->id])->save());
    }

    /**
     * Whether a sibling (same parent) already has this name, ignoring case.
     */
    public function nameTaken(string $name, ?int $parentId, ?int $exceptId = null): bool
    {
        return Folder::query()
            ->when($parentId === null, fn (Builder $query) => $query->whereNull('parent_id'), fn (Builder $query) => $query->where('parent_id', $parentId))
            ->when($exceptId !== null, fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->exists();
    }

    /**
     * The user's folders as a tree, sorted by name, each with the number of
     * notes directly inside it that the main list shows (not archived, not
     * in the trash).
     *
     * @return list<array{id: int, name: string, count: int, children: list<mixed>}>
     */
    public function tree(): array
    {
        $folders = Folder::query()
            ->withCount(['notes' => fn (Builder $notes) => $notes->whereNull('archived_at')])
            ->get(['id', 'parent_id', 'name']);

        $byParent = $folders->groupBy(fn (Folder $folder) => $folder->parent_id ?? 0);

        $build = function (int $parentId) use (&$build, $byParent): array {
            return ($byParent->get($parentId) ?? collect())
                ->sortBy(fn (Folder $folder) => mb_strtolower($folder->name), SORT_NATURAL)
                ->map(fn (Folder $folder) => [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'count' => (int) $folder->getAttribute('notes_count'),
                    'children' => $build($folder->id),
                ])
                ->values()
                ->all();
        };

        return $build(0);
    }
}
