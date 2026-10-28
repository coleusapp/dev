<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Models\Note;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * The read-only lock (B8.7): a note that can't be changed by accident until
 * it's unlocked. Locking isn't an edit, so it leaves updated_at alone.
 */
class ReadOnlyController extends Controller
{
    public function store(Note $note): RedirectResponse
    {
        Gate::authorize('update', $note);

        if ($note->read_only_at === null) {
            Note::withoutTimestamps(fn () => $note->forceFill(['read_only_at' => now()])->save());
        }

        return back();
    }

    public function destroy(Note $note): RedirectResponse
    {
        Gate::authorize('update', $note);

        Note::withoutTimestamps(fn () => $note->forceFill(['read_only_at' => null])->save());

        return back();
    }
}
