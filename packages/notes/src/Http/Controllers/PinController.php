<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Facades\Notes;
use Coleus\Notes\Models\Note;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class PinController extends Controller
{
    public function store(Note $note): RedirectResponse
    {
        Gate::authorize('update', $note);

        Notes::note()->pin($note);

        return back();
    }

    public function destroy(Note $note): RedirectResponse
    {
        Gate::authorize('update', $note);

        Notes::note()->unpin($note);

        return back();
    }
}
