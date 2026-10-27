<?php

namespace Coleus\Notes\Http\Controllers\Concerns;

use Coleus\Notes\Facades\Notes;
use Coleus\Notes\Http\Requests\NoteListRequest;
use Coleus\Notes\Http\Resources\NoteDetailResource;
use Coleus\Notes\Http\Resources\NoteResource;
use Coleus\Notes\Models\Note;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The notes index and a single note share one page: sidebar, list, editor.
 */
trait RendersNotesPage
{
    protected function notesPage(NoteListRequest $request, ?Note $note): Response
    {
        return Inertia::render('Index', [
            'notes' => NoteResource::collection(Notes::note()->list($request->tag())),
            'note' => $note ? new NoteDetailResource($note) : null,
            'tags' => Notes::note()->tagTree(),
            'filters' => $request->filters(),
        ]);
    }
}
