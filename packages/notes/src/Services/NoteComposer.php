<?php

namespace Coleus\Notes\Services;

use Coleus\Notes\Markdown\FrontMatter;
use Coleus\Notes\Models\Folder;
use Coleus\Notes\Models\Note;
use Illuminate\Support\Facades\DB;

/**
 * The note composer (O9.5): merging one note into another, and making a
 * new note from part of one.
 */
class NoteComposer
{
    public function __construct(private readonly NoteService $notes, private readonly FolderService $folders, private readonly LinkRenamer $links)
    {
        //
    }

    /**
     * Appends $source to the end of $target (its front matter left out),
     * moves $source to the trash, and points links to $source at $target.
     */
    public function merge(Note $source, Note $target): Note
    {
        return DB::transaction(function () use ($source, $target) {
            $added = trim(FrontMatter::split($source->body)[1]);
            $this->notes->update($target, ['body' => rtrim($target->body)."\n\n".$added."\n"]);

            $sourceTitle = $source->title;
            $this->notes->destroy($source);
            $this->links->relink($target->refresh(), $sourceTitle);

            return $target;
        });
    }

    /**
     * A new note with $body, in $folder (the note it came from is in).
     */
    public function extract(string $body, ?Folder $folder): Note
    {
        $note = $this->notes->store(['body' => $body]);

        if ($folder !== null) {
            $this->folders->moveNote($note, $folder);
        }

        return $note->refresh();
    }
}
