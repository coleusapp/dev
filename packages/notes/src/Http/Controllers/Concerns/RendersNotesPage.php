<?php

namespace Coleus\Notes\Http\Controllers\Concerns;

use Coleus\Notes\Facades\Notes;
use Coleus\Notes\Http\Requests\NoteListRequest;
use Coleus\Notes\Http\Resources\NoteDetailResource;
use Coleus\Notes\Http\Resources\NoteResource;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Preferences\NotePreferences;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The notes index and a single note share one page: sidebar, list, editor.
 */
trait RendersNotesPage
{
    protected function notesPage(NoteListRequest $request, ?Note $note): Response
    {
        $order = Notes::note()->sort();

        return Inertia::render('Index', [
            'sort' => ['sort' => $order['sort']->value, 'direction' => $order['direction']],
            'notes' => NoteResource::collection(Notes::note()->list($request->search(), $request->view())),
            'note' => $note ? new NoteDetailResource($note) : null,
            'counts' => Notes::note()->counts(),
            'filters' => $request->filters(),
            'preferences' => NotePreferences::all(),
        ]);
    }
}
