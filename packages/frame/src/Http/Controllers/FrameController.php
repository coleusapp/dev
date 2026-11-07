<?php

namespace Coleus\Frame\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Frame\Http\Requests\FrameRequest;
use Coleus\Frame\Jobs\FetchFrameArtworks;
use Coleus\Frame\Models\Frame;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Management pages for the current user's frames. The plain kiosk token
 * exists only in the session flash right after create/regenerate, so the
 * kiosk URL can be shown exactly once.
 */
class FrameController extends Controller
{
    public const string TOKEN_FLASH_KEY = 'frame.kiosk_token';

    public function index(): Response
    {
        Gate::authorize('viewAny', Frame::class);

        return Inertia::render('Index', [
            'frames' => Frame::query()
                ->withCount('artworks')
                ->orderBy('name')
                ->get()
                ->map(fn (Frame $frame) => [
                    'id' => $frame->id,
                    'name' => $frame->name,
                    'is_active' => $frame->is_active,
                    'artworks_count' => $frame->artworks_count,
                ]),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Frame::class);

        return Inertia::render('Edit', [
            'frame' => null,
            'kioskUrl' => null,
        ]);
    }

    public function store(FrameRequest $request): RedirectResponse
    {
        Gate::authorize('create', Frame::class);

        $token = Frame::generateToken();
        $frame = new Frame($request->frameData());
        $frame->token_hash = Frame::hashToken($token);
        $frame->save();

        return to_route('frame.edit', ['frame' => $frame])->with(self::TOKEN_FLASH_KEY, $token);
    }

    public function edit(Frame $frame): Response
    {
        Gate::authorize('update', $frame);

        $token = session(self::TOKEN_FLASH_KEY);

        return Inertia::render('Edit', [
            'frame' => [
                ...$frame->only('id', 'name', 'search_query', 'interval_seconds', 'is_active'),
                'artworks_count' => $frame->artworks()->count(),
            ],
            'kioskUrl' => $token ? route('frame.kiosk.show', ['token' => $token]) : null,
        ]);
    }

    public function update(FrameRequest $request, Frame $frame): RedirectResponse
    {
        Gate::authorize('update', $frame);

        $frame->update($request->frameData());

        return to_route('frame.edit', ['frame' => $frame]);
    }

    public function destroy(Frame $frame): RedirectResponse
    {
        Gate::authorize('delete', $frame);

        $frame->delete();

        return to_route('frame.index');
    }

    /**
     * The old kiosk URL stops working immediately.
     */
    public function regenerateToken(Frame $frame): RedirectResponse
    {
        Gate::authorize('update', $frame);

        return to_route('frame.edit', ['frame' => $frame])->with(self::TOKEN_FLASH_KEY, $frame->regenerateToken());
    }

    public function fetch(Frame $frame): RedirectResponse
    {
        Gate::authorize('update', $frame);

        FetchFrameArtworks::dispatch($frame);

        return to_route('frame.edit', ['frame' => $frame]);
    }
}
