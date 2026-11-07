<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Facades\Notes;
use Coleus\Notes\Models\Note;
use Illuminate\Http\RedirectResponse;

class PinController extends Controller
{
    public function store(Note $note): RedirectResponse
    {
        Notes::note()->pin($note);

        return back();
    }

    public function destroy(Note $note): RedirectResponse
    {
        Notes::note()->unpin($note);

        return back();
    }
}
