<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Http\Requests\WikiLinkResolveRequest;
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
}
