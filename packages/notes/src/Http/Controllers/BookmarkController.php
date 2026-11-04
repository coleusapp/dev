<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Facades\Notes;
use Coleus\Notes\Models\Note;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class BookmarkController extends Controller
{
    public function store(Note $note): RedirectResponse
    {
        Gate::authorize('update', $note);

        Notes::note()->mark($note, 'bookmarked_at', true);

        return back();
    }

    public function destroy(Note $note): RedirectResponse
    {
        Gate::authorize('update', $note);

        Notes::note()->mark($note, 'bookmarked_at', false);

        return back();
    }
}
