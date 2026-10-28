<?php

namespace Coleus\Notes\Services;

use Carbon\CarbonImmutable;
use Coleus\Notes\Models\Folder;
use Coleus\Notes\Models\Note;

/**
 * Daily notes (O9.1): one note per day, in a top-level "Daily notes"
 * folder, remembered by its date (notes.daily_on) rather than by its
 * title, so renaming it or changing the title format never loses it.
 */
class DailyNotes
{
    public const string FOLDER = 'Daily notes';

    public function __construct(private readonly NoteService $notes, private readonly FolderService $folders)
    {
        //
    }

    /**
     * The day's note: the existing one (restored from the trash if
     * needed), or a new one with $body (the browser fills in the title
     * format and the "Daily note" template).
     */
    public function open(CarbonImmutable $day, string $body): Note
    {
        $existing = Note::query()->withTrashed()->whereDate('daily_on', $day->toDateString())->latest('id')->first();

        if ($existing !== null) {
            if ($existing->trashed()) {
                $this->notes->restore($existing);
            }

            return $existing;
        }

        $note = $this->notes->store(['body' => $body]);
        $this->folders->moveNote($note, $this->folder());
        Note::withoutTimestamps(fn () => $note->forceFill(['daily_on' => $day->toDateString()])->save());

        return $note->refresh();
    }

    private function folder(): Folder
    {
        return Folder::query()->whereNull('parent_id')->whereRaw('lower(name) = ?', [mb_strtolower(self::FOLDER)])->first()
            ?? $this->folders->create(self::FOLDER, null);
    }
}
