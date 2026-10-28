<?php

namespace Coleus\Notes\Http\Controllers\Concerns;

use Coleus\Notes\Enums\NoteView;
use Coleus\Notes\Facades\Notes;
use Coleus\Notes\Http\Requests\NoteListRequest;
use Coleus\Notes\Http\Resources\NoteDetailResource;
use Coleus\Notes\Http\Resources\NoteResource;
use Coleus\Notes\Models\Folder;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Preferences\NotePreferences;
use Coleus\Notes\Search\SearchQuery;
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
        $listed = Notes::note()->list($request->tag(), $search, $request->view(), $request->folderId(), $request->smartFolder()?->rules, $request->property());
        $query = $search === null ? null : SearchQuery::parse($search);

        if ($query !== null && $request->view() !== NoteView::Recent && NotePreferences::get('search.sort') === 'relevance') {
            $listed = $query->rank($listed);
        }

        $notes = NoteResource::collection($listed);

        if ($query !== null) {
            $words = $query->highlightTerms();
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
