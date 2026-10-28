<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Facades\Notes;
use Coleus\Notes\Http\Requests\NoteVersionRequest;
use Coleus\Notes\Http\Resources\NoteVersionResource;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Models\NoteVersion;
use Coleus\Notes\Support\LineDiff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * A note's version history: list, inspect with a diff, name, restore.
 * Versions are bound scoped to their note, so another note's (or user's)
 * version id is a 404.
 */
class VersionController extends Controller
{
    public function index(Note $note): AnonymousResourceCollection
    {
        Gate::authorize('view', $note);

        return NoteVersionResource::collection($note->versions()->latest('updated_at')->latest('id')->get());
    }

    /**
     * The version's body and its diff against ?compare=<version id>, or
     * against the note as it is now.
     */
    public function show(Request $request, Note $note, NoteVersion $version): JsonResponse
    {
        Gate::authorize('view', $note);

        $compareId = $request->query('compare');
        $compare = is_numeric($compareId) ? $note->versions()->findOrFail((int) $compareId) : null;
        $diff = LineDiff::compare($compare->body ?? $version->body, $compare ? $version->body : $note->body);

        return response()->json([
            'data' => [
                ...(new NoteVersionResource($version))->toArray($request),
                'body' => $version->body,
                'compared_with' => $compare ? (new NoteVersionResource($compare))->toArray($request) : null,
                'diff' => $diff,
                'stats' => LineDiff::stats($diff),
            ],
        ]);
    }

    public function update(NoteVersionRequest $request, Note $note, NoteVersion $version): NoteVersionResource
    {
        Gate::authorize('update', $note);

        NoteVersion::withoutTimestamps(fn () => $version->update(['name' => $request->versionName()]));

        return new NoteVersionResource($version);
    }

    public function restore(Note $note, NoteVersion $version): RedirectResponse
    {
        Gate::authorize('edit', $note);
        abort_if($note->isLocked(), 409, 'This note is locked; unlock it to change it.');

        Notes::note()->update($note, ['body' => $version->body]);

        return to_route('notes.show', ['note' => $note]);
    }
}
