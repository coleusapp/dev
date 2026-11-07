<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Facades\Notes;
use Coleus\Notes\Http\Controllers\Concerns\RendersNotesPage;
use Coleus\Notes\Http\Requests\NoteListRequest;
use Coleus\Notes\Http\Requests\NoteRequest;
use Coleus\Notes\Http\Resources\NoteDetailResource;
use Coleus\Notes\Models\Note;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class NoteController extends Controller
{
    use RendersNotesPage;

    public function show(NoteListRequest $request, Note $note): Response
    {
        return $this->notesPage($request, $note);
    }

    public function store(NoteRequest $request, NoteListRequest $list): RedirectResponse
    {
        $note = Notes::note()->store($request->validated());

        return to_route('notes.show', [
            'note' => $note,
            ...Notes::note()->listQuery($list->validated()),
        ]);
    }

    public function update(NoteRequest $request, Note $note): RedirectResponse|NoteDetailResource
    {
        Notes::note()->update($note, $request->validated());

        if ($request->wantsJson() && !$request->inertia()) {
            return NoteDetailResource::make($note->refresh());
        }

        return to_route('notes.show', ['note' => $note]);
    }

    public function destroy(NoteListRequest $request, Note $note): RedirectResponse
    {
        Notes::note()->destroy($note);

        return to_route('notes.index', Notes::note()->listQuery($request->validated()));
    }
}
