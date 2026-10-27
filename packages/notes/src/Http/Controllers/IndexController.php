<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Facades\Notes;
use Coleus\Notes\Http\Resources\NoteResource;
use Coleus\Notes\Models\Note;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    public function __invoke(): Response
    {
        Gate::authorize('viewAny', Note::class);

        return Inertia::render('Index', [
            'notes' => NoteResource::collection(Notes::note()->list()),
            'note' => null,
        ]);
    }
}
