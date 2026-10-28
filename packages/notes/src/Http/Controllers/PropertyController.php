<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Http\Requests\PropertyRenameRequest;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Services\NoteProperties;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    public function __construct(private readonly NoteProperties $properties)
    {
        //
    }

    /**
     * Every property used across the user's notes.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Note::class);

        return Inertia::render('Properties', ['properties' => $this->properties->overview()]);
    }

    /**
     * Renames a property in every note that has it.
     */
    public function rename(PropertyRenameRequest $request): RedirectResponse
    {
        Gate::authorize('viewAny', Note::class);

        $count = $this->properties->rename(trim($request->validated('from')), trim($request->validated('to')));

        return back()->with('status', "Renamed in {$count} note(s).");
    }
}
