<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Bases\BaseDefinition;
use Coleus\Notes\Bases\BaseTable;
use Coleus\Notes\Http\Requests\BaseRequest;
use Coleus\Notes\Models\Base;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Services\NoteProperties;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Bases (O11.1): saved tables over notes.
 */
class BaseController extends Controller
{
    /**
     * Opens the first base, or the empty page when there is none.
     */
    public function index(): Response|RedirectResponse
    {
        Gate::authorize('viewAny', Note::class);

        $first = Base::query()->orderBy('name')->first();

        return $first ? to_route('notes.bases.show', $first) : $this->page(null);
    }

    public function show(Base $base): Response
    {
        Gate::authorize('viewAny', Note::class);

        return $this->page($base);
    }

    public function store(BaseRequest $request): RedirectResponse
    {
        Gate::authorize('create', Note::class);

        $base = Base::query()->create(['name' => trim($request->validated('name')), 'definition' => $request->definition()]);

        return to_route('notes.bases.show', $base);
    }

    public function update(BaseRequest $request, Base $base): RedirectResponse
    {
        $base->update(['name' => trim($request->validated('name')), 'definition' => $request->definition()]);

        return back();
    }

    public function destroy(Base $base): RedirectResponse
    {
        $base->delete();

        return to_route('notes.bases.index');
    }

    private function page(?Base $base): Response
    {
        return Inertia::render('Bases', [
            'bases' => Base::query()->orderBy('name')->get(['id', 'name']),
            'base' => $base ? ['id' => $base->id, 'name' => $base->name, 'definition' => BaseDefinition::normalize($base->definition)] : null,
            'table' => $base ? BaseTable::build($base->definition) : null,
            'options' => [
                'fields' => BaseDefinition::NOTE_FIELDS,
                'properties' => array_column(app(NoteProperties::class)->overview(), 'key'),
                'operators' => BaseDefinition::OPERATORS,
            ],
        ]);
    }
}
