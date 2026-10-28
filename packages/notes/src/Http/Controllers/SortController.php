<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Http\Requests\SortRequest;
use Coleus\Notes\Models\Folder;
use Coleus\Notes\Preferences\NotePreferences;
use Illuminate\Http\RedirectResponse;

class SortController extends Controller
{
    /**
     * Sets how the list is ordered. With a folder_id the choice is that
     * folder's own (Apple Notes' "sort per folder"); null sort/direction for
     * a folder goes back to the default. Without a folder it changes the
     * default for every list.
     */
    public function update(SortRequest $request): RedirectResponse
    {
        $folder = $request->folder();
        $sort = $request->validated('sort');
        $direction = $request->validated('direction');

        if ($folder instanceof Folder) {
            Folder::withoutTimestamps(fn () => $folder->forceFill([
                'sort' => $sort,
                'sort_direction' => $sort === null ? null : ($direction ?? 'desc'),
            ])->save());
        } elseif ($sort !== null) {
            NotePreferences::save(['list.sort' => $sort, 'list.sort_direction' => $direction ?? 'desc']);
        }

        return back();
    }
}
