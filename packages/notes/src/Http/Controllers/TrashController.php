<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Enums\NoteView;
use Coleus\Notes\Facades\Notes;
use Coleus\Notes\Models\Note;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Notes in the trash: put them back, or delete them for good.
 * Moving a note to the trash is NoteController::destroy.
 */
class TrashController extends Controller
{
    public function restore(Note $note): RedirectResponse
    {
        Gate::authorize('restore', $note);

        Notes::note()->restore($note);

        return to_route('notes.show', ['note' => $note]);
    }

    public function destroy(Note $note): RedirectResponse
    {
        Gate::authorize('forceDelete', $note);

        Notes::note()->forceDelete($note);

        return to_route('notes.index', ['view' => NoteView::Trash->value]);
    }

    public function empty(): RedirectResponse
    {
        Gate::authorize('viewAny', Note::class);

        Notes::note()->emptyTrash();

        return to_route('notes.index', ['view' => NoteView::Trash->value]);
    }
}
