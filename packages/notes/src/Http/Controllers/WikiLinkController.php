<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Http\Requests\LinkPreviewRequest;
use Coleus\Notes\Http\Requests\WikiLinkResolveRequest;
use Coleus\Notes\Markdown\NoteMarkdown;
use Coleus\Notes\Markdown\WikiLinks\WikiLinkResolver;
use Coleus\Notes\Markdown\WikiLinks\WikiLinks;
use Coleus\Notes\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Resolves a [[wiki link]] title for the visual editor, with the same rules
 * as the rendered preview (WikiLinkResolver): case-insensitive, the user's
 * live notes, never the linking note itself.
 */
class WikiLinkController extends Controller
{
    public function resolve(WikiLinkResolveRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', Note::class);

        $title = WikiLinks::normalizeTitle($request->string('title')->toString());
        $from = $request->integer('from') ?: null;

        return response()->json([
            'data' => ['id' => WikiLinkResolver::forBody("[[{$title}]]", $from)->resolve($title)],
        ]);
    }

    /**
     * The note a link leads to, rendered, for the hover preview (O2.10);
     * null when no note has that name.
     */
    public function preview(LinkPreviewRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', Note::class);

        if ($request->filled('note')) {
            $id = $request->integer('note');
        } else {
            $title = WikiLinks::normalizeTitle($request->string('title')->toString());
            $id = WikiLinkResolver::forBody("[[{$title}]]", $request->integer('from') ?: null)->resolve($title);
        }

        $note = $id === null ? null : Note::query()->find($id);

        return response()->json([
            'data' => $note === null ? null : [
                'id' => $note->id,
                'title' => $note->title,
                'locked' => $note->isLocked(),
                'html' => $note->isLocked() ? '' : NoteMarkdown::toHtml($note->body, WikiLinkResolver::forBody($note->body, $note->id)),
            ],
        ]);
    }

    /**
     * A note's headings, for suggesting [[Note#Heading]] while typing a link.
     */
    public function headings(Note $note): JsonResponse
    {
        Gate::authorize('view', $note);

        return response()->json(['data' => WikiLinks::headings($note->body)]);
    }
}
