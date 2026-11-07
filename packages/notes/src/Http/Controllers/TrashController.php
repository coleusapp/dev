<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Enums\NoteView;
use Coleus\Notes\Facades\Notes;
use Coleus\Notes\Models\Note;
use Illuminate\Http\RedirectResponse;

class TrashController extends Controller
{
    public function restore(Note $note): RedirectResponse
    {
        Notes::note()->restore($note);

        return to_route('notes.show', ['note' => $note]);
    }

    public function destroy(Note $note): RedirectResponse
    {
        Notes::note()->forceDelete($note);

        return to_route('notes.index', ['view' => NoteView::Trash->value]);
    }

    public function empty(): RedirectResponse
    {
        Notes::note()->emptyTrash();

        return to_route('notes.index', ['view' => NoteView::Trash->value]);
    }
}
