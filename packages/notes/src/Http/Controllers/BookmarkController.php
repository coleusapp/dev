<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Http\Requests\BookmarkRequest;
use Coleus\Notes\Http\Requests\SavedSearchRequest;
use Coleus\Notes\Models\Bookmark;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Services\BookmarkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class BookmarkController extends Controller
{
    public function __construct(private readonly BookmarkService $bookmarks)
    {
        //
    }

    /**
     * Bookmarks a note, or one of its headings with {heading}.
     */
    public function store(BookmarkRequest $request, Note $note): RedirectResponse
    {
        Gate::authorize('update', $note);

        $this->bookmarks->bookmarkNote($note, $request->text('heading'));

        return back();
    }

    /**
     * Saves the current list filters as a bookmarked search.
     */
    public function storeSearch(SavedSearchRequest $request): RedirectResponse
    {
        $filters = $request->savedFilters();

        if ($filters === []) {
            throw ValidationException::withMessages(['label' => 'Filter or search the list first, then save it.']);
        }

        $this->bookmarks->bookmarkSearch($filters, trim((string) $request->validated('label')));

        return back();
    }

    /**
     * Renames a bookmark and/or puts it in a group (null = no group).
     */
    public function update(BookmarkRequest $request, Bookmark $bookmark): RedirectResponse
    {
        $this->bookmarks->update($bookmark, $request->text('label'), $request->text('group'));

        return back();
    }

    public function move(Request $request, Bookmark $bookmark): RedirectResponse
    {
        $request->validate(['step' => ['required', 'integer', 'in:-1,1']], ['step.in' => 'Move one place up or down.']);

        $this->bookmarks->move($bookmark, (int) $request->input('step'));

        return back();
    }

    public function destroy(Bookmark $bookmark): RedirectResponse
    {
        $bookmark->delete();

        return back();
    }
}
