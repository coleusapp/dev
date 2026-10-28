<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Markdown\WikiLinks\UnlinkedMentions;
use Coleus\Notes\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Unlinked mentions of a note (O2.8): fetched when that section is opened,
 * since finding them reads many notes.
 */
class MentionController extends Controller
{
    public function index(Note $note): JsonResponse
    {
        Gate::authorize('view', $note);

        return response()->json(['data' => UnlinkedMentions::for($note)->all()]);
    }

    /**
     * Links every mention of $note in $source.
     */
    public function store(Note $note, Note $source): JsonResponse
    {
        Gate::authorize('view', $note);
        Gate::authorize('update', $source);

        return response()->json(['data' => ['count' => UnlinkedMentions::link($source, $note)]]);
    }
}
