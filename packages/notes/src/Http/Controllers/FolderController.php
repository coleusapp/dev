<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Http\Requests\FolderRequest;
use Coleus\Notes\Http\Requests\MoveNoteRequest;
use Coleus\Notes\Models\Folder;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Services\FolderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class FolderController extends Controller
{
    public function __construct(private readonly FolderService $folders)
    {
        //
    }

    public function store(FolderRequest $request): RedirectResponse
    {
        Gate::authorize('create', Note::class);

        $folder = $this->folders->create($request->validated('name'), $request->parent());

        return to_route('notes.index', ['folder' => $folder->id]);
    }

    /**
     * Renames a folder and/or moves it under another folder (or to the top).
     */
    public function update(FolderRequest $request, Folder $folder): RedirectResponse
    {
        Gate::authorize('create', Note::class);

        $this->folders->update($folder, $request->validated('name'), $request->parent());

        return back();
    }

    public function destroy(Folder $folder): RedirectResponse
    {
        Gate::authorize('create', Note::class);

        $this->folders->delete($folder);

        return to_route('notes.index');
    }

    /**
     * Moves a note into a folder, or to the top level with folder_id null.
     */
    public function moveNote(MoveNoteRequest $request, Note $note): RedirectResponse
    {
        Gate::authorize('update', $note);

        $this->folders->moveNote($note, $request->folder());

        return back();
    }
}
