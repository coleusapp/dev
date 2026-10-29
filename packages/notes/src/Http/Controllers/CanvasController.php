<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Canvas\CanvasCards;
use Coleus\Notes\Canvas\CanvasFiles;
use Coleus\Notes\Canvas\JsonCanvas;
use Coleus\Notes\Http\Requests\CanvasImportRequest;
use Coleus\Notes\Http\Requests\CanvasRequest;
use Coleus\Notes\Models\Canvas;
use Coleus\Notes\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Canvases (O10.1): boards of cards and arrows, saved as JSON Canvas.
 */
class CanvasController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Note::class);

        return Inertia::render('Canvases', [
            'canvases' => Canvas::query()->orderBy('name')->get(['id', 'name', 'data', 'updated_at'])->map(fn (Canvas $canvas) => [
                'id' => $canvas->id,
                'name' => $canvas->name,
                'cards' => count($canvas->data['nodes'] ?? []),
                'updated_at' => $canvas->updated_at?->toIso8601String(),
            ]),
        ]);
    }

    public function show(Canvas $canvas): Response
    {
        Gate::authorize('viewAny', Note::class);

        $data = JsonCanvas::normalize($canvas->data);

        return Inertia::render('Canvas', [
            'canvas' => ['id' => $canvas->id, 'name' => $canvas->name, 'data' => $data],
            'cards' => CanvasCards::for($data),
        ]);
    }

    public function store(CanvasRequest $request): RedirectResponse
    {
        Gate::authorize('create', Note::class);

        $canvas = Canvas::query()->create(['name' => trim($request->validated('name')), 'data' => JsonCanvas::empty()]);

        return to_route('notes.canvases.show', $canvas);
    }

    /**
     * Saves the board as the page edits it (autosave) and answers with
     * what its note and attachment cards show, for cards just added.
     */
    public function update(CanvasRequest $request, Canvas $canvas): JsonResponse
    {
        $data = $request->has('data') ? JsonCanvas::normalize((array) $request->validated('data')) : JsonCanvas::normalize($canvas->data);

        $canvas->update([
            ...($request->has('name') ? ['name' => trim($request->validated('name'))] : []),
            'data' => $data,
        ]);

        return response()->json(['data' => ['name' => $canvas->name, 'cards' => CanvasCards::for($data)]]);
    }

    /**
     * Downloads the board as a .canvas file (O10.2).
     */
    public function export(Canvas $canvas, CanvasFiles $files): StreamedResponse
    {
        Gate::authorize('viewAny', Note::class);

        $json = (string) json_encode($files->export($canvas), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return response()->streamDownload(function () use ($json) {
            echo $json;
        }, CanvasFiles::fileName($canvas->name).'.canvas', ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    /**
     * Makes a canvas from a .canvas file (O10.2).
     */
    public function import(CanvasImportRequest $request, CanvasFiles $files): RedirectResponse
    {
        Gate::authorize('create', Note::class);

        $canvas = $files->import((array) $request->canvasJson(), $request->canvasName());

        return to_route('notes.canvases.show', $canvas);
    }

    public function destroy(Canvas $canvas): RedirectResponse
    {
        $canvas->delete();

        return to_route('notes.canvases.index');
    }
}
