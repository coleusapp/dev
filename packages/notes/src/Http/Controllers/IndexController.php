<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Http\Controllers\Concerns\RendersNotesPage;
use Coleus\Notes\Http\Requests\NoteListRequest;
use Inertia\Response;

class IndexController extends Controller
{
    use RendersNotesPage;

    public function __invoke(NoteListRequest $request): Response
    {
        return $this->notesPage($request);
    }
}
