<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Models\NoteAlias;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Every live note's title and aliases for the quick switcher, which ranks
 * them on the client as you type. Most recently opened (then edited) first,
 * so an empty query lists recents. Archived notes are included and flagged;
 * trashed notes are not.
 */
class SwitcherController extends Controller
{
    public function __invoke(): JsonResponse
    {
        Gate::authorize('viewAny', Note::class);

        $notes = Note::query()
            ->with('aliases:id,note_id,alias')
            ->orderByRaw('opened_at is null')
            ->orderByDesc('opened_at')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get(['id', 'title', 'archived_at', 'opened_at', 'updated_at']);

        return response()->json([
            'data' => $notes->map(fn (Note $note): array => [
                'id' => $note->id,
                'title' => $note->title,
                'aliases' => $note->aliases->map(fn (NoteAlias $alias): string => $alias->alias)->values()->all(),
                'archived' => $note->archived_at !== null,
            ])->all(),
        ]);
    }
}
