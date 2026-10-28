<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Services\FolderService;
use Coleus\Notes\Services\TaskList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    /**
     * The Tasks view (O9.6): checklist items across notes, open by default,
     * filtered by tag and folder.
     */
    public function index(Request $request, TaskList $tasks): Response
    {
        Gate::authorize('viewAny', Note::class);

        $filters = $request->validate([
            'state' => ['nullable', Rule::in(['open', 'done', 'all'])],
            'tag' => ['nullable', 'string', 'max:191'],
            'folder' => ['nullable', 'integer'],
        ]);

        $filters = ['state' => $filters['state'] ?? 'open', 'tag' => $filters['tag'] ?? null, 'folder' => isset($filters['folder']) ? (int) $filters['folder'] : null];

        return Inertia::render('Tasks', [
            'groups' => $tasks->grouped($filters),
            'filters' => $filters,
            'folders' => app(FolderService::class)->tree(),
        ]);
    }
}
