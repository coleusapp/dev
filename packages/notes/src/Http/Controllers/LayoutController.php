<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Http\Requests\LayoutRequest;
use Coleus\Notes\Models\Folder;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Preferences\NotePreferences;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class LayoutController extends Controller
{
    /**
     * Shows notes as a list or a gallery of cards (A1.4). In a folder the
     * choice is that folder's own (as in Apple Notes); elsewhere it's the
     * default for every list.
     */
    public function update(LayoutRequest $request): RedirectResponse
    {
        Gate::authorize('viewAny', Note::class);

        $folder = $request->folder();
        $layout = (string) $request->validated('layout');

        if ($folder instanceof Folder) {
            Folder::withoutTimestamps(fn () => $folder->forceFill(['layout' => $layout])->save());
        } else {
            NotePreferences::save(['list.layout' => $layout]);
        }

        return back();
    }
}
