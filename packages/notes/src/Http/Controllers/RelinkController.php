<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Http\Requests\RelinkRequest;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Services\LinkRenamer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Updating links after a rename (O2.6): how many notes still link to the
 * old title, and pointing them at the new one.
 */
class RelinkController extends Controller
{
    public function __construct(private readonly LinkRenamer $renamer)
    {
        //
    }

    public function show(RelinkRequest $request, Note $note): JsonResponse
    {
        Gate::authorize('view', $note);

        return response()->json(['data' => ['count' => $this->renamer->staleLinks($note, $request->oldTitle())->count()]]);
    }

    public function store(RelinkRequest $request, Note $note): JsonResponse
    {
        Gate::authorize('update', $note);

        if (preg_match('/[#|]/', $note->title) === 1) {
            return response()->json(['message' => 'Links can\'t point at a title with "#" or "|" in it.'], 422);
        }

        return response()->json(['data' => ['count' => $this->renamer->relink($note, $request->oldTitle())]]);
    }
}
