<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Facades\Notes;
use Coleus\Notes\Http\Requests\ChecklistMoveRequest;
use Coleus\Notes\Http\Requests\ChecklistRequest;
use Coleus\Notes\Http\Resources\NoteDetailResource;
use Coleus\Notes\Markdown\Checklist;
use Coleus\Notes\Models\Note;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ChecklistController extends Controller
{
    /**
     * Checks or unchecks the task item on one source line of the note.
     */
    public function update(ChecklistRequest $request, Note $note, int $line): NoteDetailResource
    {
        Gate::authorize('edit', $note);

        $body = Checklist::toggle($note->body, $line, $request->boolean('checked'));

        if ($body === null) {
            throw ValidationException::withMessages([
                'line' => 'That line is no longer a checklist item. Reload the note and try again.',
            ]);
        }

        Notes::note()->update($note, ['body' => $body]);

        return new NoteDetailResource($note->refresh());
    }

    /**
     * Moves a task item (with its sub-items) before another item of the
     * same list, or to the end of its list.
     */
    public function move(ChecklistMoveRequest $request, Note $note): NoteDetailResource
    {
        Gate::authorize('edit', $note);

        $beforeLine = $request->validated('before_line');
        $body = Checklist::move($note->body, (int) $request->validated('line'), $beforeLine === null ? null : (int) $beforeLine);

        if ($body === null) {
            throw ValidationException::withMessages([
                'line' => 'That item can only move within its own checklist. Reload the note and try again.',
            ]);
        }

        Notes::note()->update($note, ['body' => $body]);

        return new NoteDetailResource($note->refresh());
    }
}
