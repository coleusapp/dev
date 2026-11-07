<?php

namespace Coleus\Notes\Http\Controllers\Concerns;

use Coleus\Notes\Facades\Notes;
use Coleus\Notes\Http\Requests\NoteListRequest;
use Coleus\Notes\Http\Resources\NoteCountsResource;
use Coleus\Notes\Http\Resources\NoteDetailResource;
use Coleus\Notes\Http\Resources\NoteFiltersResource;
use Coleus\Notes\Http\Resources\NoteResource;
use Coleus\Notes\Models\Note;
use Inertia\Inertia;
use Inertia\Response;

trait RendersNotesPage
{
    protected function notesPage(NoteListRequest $request, ?Note $note = null): Response
    {
        return Inertia::render('Index', [
            'notes' => NoteResource::collection(Notes::note()->list($request->validated())),
            'note' => $note ? NoteDetailResource::make($note) : null,
            'counts' => NoteCountsResource::make(Notes::note()->counts()),
            'filters' => NoteFiltersResource::make(Notes::note()->filters($request->validated())),
        ]);
    }
}
