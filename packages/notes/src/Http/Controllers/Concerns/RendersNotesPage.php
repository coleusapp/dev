<?php

namespace Coleus\Notes\Http\Controllers\Concerns;

use Coleus\Notes\Facades\Notes;
use Coleus\Notes\Http\Requests\NoteListRequest;
use Coleus\Notes\Http\Resources\NoteDetailResource;
use Coleus\Notes\Http\Resources\NoteResource;
use Coleus\Notes\Models\Folder;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Preferences\NotePreferences;
use Coleus\Notes\Search\NoteSearch;
use Coleus\Notes\Services\BookmarkService;
use Coleus\Notes\Services\FolderService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The notes index and a single note share one page: sidebar, list, editor.
 */
trait RendersNotesPage
{
    protected function notesPage(NoteListRequest $request, ?Note $note): Response
    {
        $search = $request->search();
        $notes = NoteResource::collection(Notes::note()->list($request->tag(), $search, $request->view(), $request->folderId(), $request->smartFolder()?->rules));

        if ($search !== null) {
            $words = NoteSearch::words($search);
            $notes->collection->each(fn (NoteResource $resource) => $resource->highlight($words));
        }

        $order = Notes::note()->sortFor($request->folderId() === null ? null : Folder::query()->find($request->folderId()));

        return Inertia::render('Index', [
            'sort' => ['sort' => $order['sort']->value, 'direction' => $order['direction'], 'scope' => $order['scope']],
            'notes' => $notes,
            'note' => $note ? new NoteDetailResource($note) : null,
            'tags' => Notes::note()->tagTree(),
            'folders' => app(FolderService::class)->tree(),
            'counts' => Notes::note()->counts(),
            'smartFolders' => Notes::note()->smartFolders(),
            'bookmarks' => app(BookmarkService::class)->grouped(),
            'filters' => $request->filters(),
            'preferences' => NotePreferences::all(),
        ]);
    }
}
