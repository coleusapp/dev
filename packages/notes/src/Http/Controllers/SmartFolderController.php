<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Http\Requests\SmartFolderRequest;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Models\SmartFolder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SmartFolderController extends Controller
{
    public function store(SmartFolderRequest $request): RedirectResponse
    {
        Gate::authorize('create', Note::class);

        $smartFolder = SmartFolder::query()->create(['name' => trim($request->validated('name')), 'rules' => $request->smartRules()]);

        return to_route('notes.index', ['smart' => $smartFolder->id]);
    }

    public function update(SmartFolderRequest $request, SmartFolder $smartFolder): RedirectResponse
    {
        $smartFolder->update(['name' => trim($request->validated('name')), 'rules' => $request->smartRules()]);

        return back();
    }

    public function destroy(SmartFolder $smartFolder): RedirectResponse
    {
        $smartFolder->delete();

        return to_route('notes.index');
    }
}
