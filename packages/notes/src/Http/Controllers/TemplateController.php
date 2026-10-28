<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Models\Folder;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Services\FolderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Templates (O9.3 / O9.4) are ordinary notes in a top-level folder named
 * "Templates" (any case, as in Obsidian), including its sub-folders. The
 * browser fills in their {{variables}} when one is used.
 */
class TemplateController extends Controller
{
    public const string FOLDER = 'Templates';

    public function index(FolderService $folders): JsonResponse
    {
        Gate::authorize('viewAny', Note::class);

        $root = Folder::query()->whereNull('parent_id')->whereRaw('lower(name) = ?', [mb_strtolower(self::FOLDER)])->first();

        $templates = $root === null ? collect() : Note::query()
            ->whereIn('folder_id', $folders->subtreeIds($root))
            ->whereNull('lock_key_id')
            ->orderBy('title')
            ->get(['id', 'title', 'body']);

        return response()->json(['data' => $templates->map(fn (Note $note) => ['id' => $note->id, 'title' => $note->title, 'body' => $note->body])->all()]);
    }
}
