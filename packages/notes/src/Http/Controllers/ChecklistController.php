<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Facades\Notes;
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
        Gate::authorize('update', $note);

        $body = Checklist::toggle($note->body, $line, $request->boolean('checked'));

        if ($body === null) {
            throw ValidationException::withMessages([
                'line' => 'That line is no longer a checklist item. Reload the note and try again.',
            ]);
        }

        Notes::note()->update($note, ['body' => $body]);

        return new NoteDetailResource($note->refresh());
    }
}
