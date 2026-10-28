<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\LinkPreviews\LinkPreviews;
use Coleus\Notes\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LinkPreviewController extends Controller
{
    /**
     * A card for a link on its own line (A4.1): title, description, image
     * and site, or null when the page can't (or may not) be read.
     */
    public function show(Request $request, LinkPreviews $previews): JsonResponse
    {
        Gate::authorize('viewAny', Note::class);

        $url = $request->validate(['url' => ['required', 'string', 'max:2000', 'url:http,https']])['url'];

        return response()->json(['data' => $previews->get($url)?->toClient()]);
    }
}
