<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Facades\Notes;
use Coleus\Notes\Models\Note;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ArchiveController extends Controller
{
    /**
     * Archiving takes the note out of the main list, so the main list is shown next.
     */
    public function store(Note $note): RedirectResponse
    {
        Gate::authorize('update', $note);

        Notes::note()->archive($note);

        return to_route('notes.index');
    }

    /**
     * Unarchiving brings the note back into the main list, opened.
     */
    public function destroy(Note $note): RedirectResponse
    {
        Gate::authorize('update', $note);

        Notes::note()->unarchive($note);

        return to_route('notes.show', ['note' => $note]);
    }
}
