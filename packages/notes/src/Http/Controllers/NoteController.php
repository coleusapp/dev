<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Facades\Notes;
use Coleus\Notes\Http\Requests\NoteRequest;
use Coleus\Notes\Http\Resources\NoteResource;
use Coleus\Notes\Models\Note;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    public function show(Note $note): Response
    {
        Gate::authorize('view', $note);

        return Inertia::render('Index', [
            'notes' => NoteResource::collection(Notes::note()->list()),
            'note' => new NoteResource($note),
        ]);
    }

    public function store(NoteRequest $request): RedirectResponse
    {
        Gate::authorize('create', Note::class);

        $note = Notes::note()->store($request->noteData());

        return to_route('notes.show', ['note' => $note]);
    }

    public function update(NoteRequest $request, Note $note): RedirectResponse
    {
        Gate::authorize('update', $note);

        Notes::note()->update($note, $request->noteData());

        return to_route('notes.show', ['note' => $note]);
    }

    public function destroy(Note $note): RedirectResponse
    {
        Gate::authorize('delete', $note);

        Notes::note()->destroy($note);

        return to_route('notes.index');
    }
}
