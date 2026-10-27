<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Enums\NoteView;
use Coleus\Notes\Facades\Notes;
use Coleus\Notes\Http\Controllers\Concerns\RendersNotesPage;
use Coleus\Notes\Http\Requests\NoteListRequest;
use Coleus\Notes\Http\Requests\NoteRequest;
use Coleus\Notes\Http\Resources\NoteDetailResource;
use Coleus\Notes\Models\Note;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Response;

class NoteController extends Controller
{
    use RendersNotesPage;

    public function show(NoteListRequest $request, Note $note): Response
    {
        Gate::authorize('view', $note);

        return $this->notesPage($request, $note);
    }

    public function store(NoteRequest $request): RedirectResponse
    {
        Gate::authorize('create', Note::class);

        $note = Notes::note()->store($request->noteData());

        return to_route('notes.show', ['note' => $note, ...$this->listQuery($request)]);
    }

    /**
     * Inertia visits get a redirect; the editor's autosave sends plain JSON
     * requests and gets the saved note back so it can refresh the list row.
     */
    public function update(NoteRequest $request, Note $note): RedirectResponse|NoteDetailResource
    {
        Gate::authorize('update', $note);

        Notes::note()->update($note, $request->noteData());

        if ($request->wantsJson() && ! $request->inertia()) {
            return new NoteDetailResource($note->refresh());
        }

        return to_route('notes.show', ['note' => $note]);
    }

    public function destroy(Request $request, Note $note): RedirectResponse
    {
        Gate::authorize('delete', $note);

        Notes::note()->destroy($note);

        return to_route('notes.index', $this->listQuery($request));
    }

    /**
     * Keeps the list filters from the query string across a redirect; the
     * page it lands on validates them.
     *
     * @return array<string, string>
     */
    private function listQuery(Request $request): array
    {
        return collect(['tag', 'q', 'view'])
            ->mapWithKeys(fn (string $key) => [$key => $request->query($key)])
            ->filter(fn (mixed $value, string $key) => is_string($value) && $value !== '' && ! ($key === 'view' && $value === NoteView::Notes->value))
            ->all();
    }
}
