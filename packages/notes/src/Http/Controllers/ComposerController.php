<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Models\Folder;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Services\NoteComposer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ComposerController extends Controller
{
    public function __construct(private readonly NoteComposer $composer)
    {
        //
    }

    /**
     * Merges this note into another (O9.5) and opens the result.
     */
    public function merge(Request $request, Note $note): RedirectResponse
    {
        $target = Note::query()->findOrFail($request->validate(['into' => ['required', 'integer']])['into']);

        Gate::authorize('edit', $note);
        Gate::authorize('edit', $target);

        if ($target->is($note) || $note->isLocked() || $target->isLocked()) {
            throw ValidationException::withMessages(['into' => $target->is($note) ? "A note can't be merged into itself." : 'Locked notes can’t be merged.']);
        }

        return to_route('notes.show', ['note' => $this->composer->merge($note, $target)]);
    }

    /**
     * Makes a new note from a selection (O9.5); the editor then puts a link
     * or an embed to it where the selection was.
     */
    public function extract(Request $request): JsonResponse
    {
        Gate::authorize('create', Note::class);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:100000'],
            'folder_id' => ['nullable', 'integer'],
        ]);

        $note = $this->composer->extract($data['body'], isset($data['folder_id']) ? Folder::query()->find($data['folder_id']) : null);

        if ($note->title === '') {
            $note->forceDelete();

            throw ValidationException::withMessages(['body' => 'The selection needs some text to become a note.']);
        }

        return response()->json(['data' => ['id' => $note->id, 'title' => $note->title]], 201);
    }
}
