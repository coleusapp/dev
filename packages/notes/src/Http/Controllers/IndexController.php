<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Http\Controllers\Concerns\RendersNotesPage;
use Coleus\Notes\Http\Requests\NoteListRequest;
use Coleus\Notes\Models\Note;
use Illuminate\Support\Facades\Gate;
use Inertia\Response;

class IndexController extends Controller
{
    use RendersNotesPage;

    public function __invoke(NoteListRequest $request): Response
    {
        Gate::authorize('viewAny', Note::class);

        return $this->notesPage($request, null);
    }
}
