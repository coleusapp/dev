<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Services\QuickNotes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QuickNoteController extends Controller
{
    /**
     * The address a "Quick Note" button anywhere in Coleus links to
     * (/notes/quick?from=/calendar&title=Calendar): Notes opens with the
     * quick note composer, remembering the page it came from.
     */
    public function create(Request $request): RedirectResponse
    {
        Gate::authorize('create', Note::class);

        $from = QuickNotes::safePath($request->string('from')->toString());

        return to_route('notes.index', array_filter([
            'quick' => 1,
            'from' => $from,
            'from_title' => $from === null ? null : mb_substr($request->string('title')->toString(), 0, 200),
        ]));
    }

    public function store(Request $request, QuickNotes $quickNotes): JsonResponse
    {
        Gate::authorize('create', Note::class);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:100000'],
            'from' => ['nullable', 'string', 'max:2000'],
            'from_title' => ['nullable', 'string', 'max:200'],
        ]);

        $note = $quickNotes->create($data['body'], $data['from'] ?? null, $data['from_title'] ?? null);

        return response()->json(['data' => ['id' => $note->id, 'title' => $note->title]], 201);
    }
}
