<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Http\Requests\FolderRequest;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Services\FolderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class FolderController extends Controller
{
    public function __construct(private readonly FolderService $folders)
    {
        //
    }

    public function store(FolderRequest $request): RedirectResponse
    {
        Gate::authorize('create', Note::class);

        $folder = $this->folders->create($request->validated('name'), $request->parent());

        return to_route('notes.index', ['folder' => $folder->id]);
    }
}
