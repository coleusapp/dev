<?php

namespace Coleus\Notes\Widgets;

use Coleus\Notes\Enums\NoteView;
use Coleus\Notes\Models\Note;

/**
 * Data for a home-dashboard "Notes" widget (A9.3): the signed-in user's
 * pinned notes, their most recently opened ones, and where "New note"
 * goes. It follows the coleus/widgets convention (a static getData()), so
 * a dashboard can list it next to the other widgets; it doesn't extend
 * Coleus\Widgets\ChartWidget, so Notes doesn't depend on that package.
 * Locked notes show their title only (as everywhere).
 */
class NotesWidget
{
    public const int LIMIT = 5;

    /**
     * @return array{pinned: list<array{id: int, title: string, url: string, locked: bool}>, recent: list<array{id: int, title: string, url: string, locked: bool}>, new_note_url: string, all_notes_url: string}
     */
    public static function getData(): array
    {
        $row = fn (Note $note): array => [
            'id' => $note->id,
            'title' => $note->title,
            'url' => route('notes.show', ['note' => $note->id]),
            'locked' => $note->isLocked(),
        ];

        $pinned = Note::query()->whereNotNull('pinned_at')->whereNull('archived_at')->latest('pinned_at')->limit(self::LIMIT)->get();

        $recent = NoteView::Recent->scope(Note::query())
            ->whereKeyNot($pinned->modelKeys())
            ->limit(self::LIMIT)
            ->get();

        return [
            'pinned' => $pinned->map($row)->values()->all(),
            'recent' => $recent->map($row)->values()->all(),
            'new_note_url' => route('notes.quick.create'),
            'all_notes_url' => route('notes.index'),
        ];
    }
}
