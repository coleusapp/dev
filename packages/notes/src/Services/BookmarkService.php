<?php

namespace Coleus\Notes\Services;

use Coleus\Notes\Enums\BookmarkType;
use Coleus\Notes\Models\Bookmark;
use Coleus\Notes\Models\Note;
use Illuminate\Database\Eloquent\Builder;

class BookmarkService
{
    /**
     * Bookmarks a note, or a heading in it. Bookmarking the same thing twice
     * returns the existing bookmark.
     */
    public function bookmarkNote(Note $note, ?string $heading): Bookmark
    {
        $type = $heading === null ? BookmarkType::Note : BookmarkType::Heading;

        $existing = Bookmark::query()
            ->where('type', $type->value)
            ->where('note_id', $note->id)
            ->when($heading !== null, fn (Builder $query) => $query->where('heading', $heading))
            ->first();

        if ($existing) {
            return $existing;
        }

        $bookmark = new Bookmark;
        $bookmark->forceFill(['type' => $type, 'note_id' => $note->id, 'heading' => $heading, 'position' => $this->nextPosition(null)]);
        $bookmark->save();

        return $bookmark;
    }

    /**
     * Saves the current list filters as a bookmarked search (O7.6).
     *
     * @param  array<string, mixed>  $filters
     */
    public function bookmarkSearch(array $filters, string $label): Bookmark
    {
        $bookmark = new Bookmark(['label' => $label]);
        $bookmark->forceFill(['type' => BookmarkType::Search, 'filters' => $filters, 'position' => $this->nextPosition(null)]);
        $bookmark->save();

        return $bookmark;
    }

    public function update(Bookmark $bookmark, ?string $label, ?string $group): void
    {
        $groupChanged = $bookmark->group !== $group;
        $bookmark->forceFill(['label' => $label, 'group' => $group]);

        if ($groupChanged) {
            $bookmark->position = $this->nextPosition($group);
        }

        $bookmark->save();
    }

    /**
     * Moves a bookmark one place up or down within its group.
     */
    public function move(Bookmark $bookmark, int $step): void
    {
        $siblings = Bookmark::query()
            ->when($bookmark->group === null, fn (Builder $query) => $query->whereNull('group'), fn (Builder $query) => $query->where('group', $bookmark->group))
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->values();
        $index = $siblings->search(fn (Bookmark $sibling) => $sibling->is($bookmark));
        $target = $index === false ? null : $siblings->get($index + $step);

        if ($index === false || $target === null) {
            return;
        }

        $ordered = $siblings->all();
        [$ordered[$index], $ordered[$index + $step]] = [$ordered[$index + $step], $ordered[$index]];

        foreach ($ordered as $position => $sibling) {
            Bookmark::withoutTimestamps(fn () => $sibling->forceFill(['position' => $position])->save());
        }
    }

    /**
     * Bookmarks grouped for the sidebar: ungrouped first, then groups by
     * name. Bookmarks of trashed notes are left out (they come back with
     * the note); deleting a note deletes its bookmarks.
     *
     * @return list<array{group: string|null, bookmarks: list<array<string, mixed>>}>
     */
    public function grouped(): array
    {
        $bookmarks = Bookmark::query()
            ->with('note:id,title,deleted_at')
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->reject(fn (Bookmark $bookmark) => in_array($bookmark->type, [BookmarkType::Note, BookmarkType::Heading], true) && $bookmark->note === null);

        return $bookmarks
            ->groupBy(fn (Bookmark $bookmark) => $bookmark->group ?? '')
            ->sortKeysUsing(fn (string $a, string $b) => ($a === '' ? -1 : ($b === '' ? 1 : strnatcasecmp($a, $b))))
            ->map(fn ($items, string $group) => [
                'group' => $group === '' ? null : $group,
                'bookmarks' => $items->map(fn (Bookmark $bookmark) => [
                    'id' => $bookmark->id,
                    'type' => $bookmark->type->value,
                    'note_id' => $bookmark->note_id,
                    'heading' => $bookmark->heading,
                    'filters' => $bookmark->filters,
                    'label' => $bookmark->label,
                    'title' => $bookmark->label ?? $this->defaultTitle($bookmark),
                    'group' => $bookmark->group,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    private function defaultTitle(Bookmark $bookmark): string
    {
        return match ($bookmark->type) {
            BookmarkType::Note => $bookmark->note?->title ?: 'Untitled note',
            BookmarkType::Heading => (string) $bookmark->heading,
            BookmarkType::Search => 'Saved search',
        };
    }

    private function nextPosition(?string $group): int
    {
        return (int) Bookmark::query()
            ->when($group === null, fn (Builder $query) => $query->whereNull('group'), fn (Builder $query) => $query->where('group', $group))
            ->max('position') + 1;
    }
}
