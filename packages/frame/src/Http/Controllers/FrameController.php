<?php

namespace Coleus\Frame\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Frame\Facades\Frames;
use Coleus\Frame\Http\Requests\FrameRequest;
use Coleus\Frame\Models\Frame;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FrameController extends Controller
{
    public const string TOKEN_FLASH_KEY = 'frame.kiosk_token';

    public function index(): Response
    {
        return Inertia::render('Index', [
            'frames' => Frames::frame()
                ->list()
                ->map(fn (Frame $frame) => $frame->only('id', 'name', 'is_active', 'artworks_count')),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Edit', [
            'frame' => null,
            'kioskUrl' => null,
            'sources' => Frames::artwork()->sourceOptions(),
        ]);
    }

    public function store(FrameRequest $request): RedirectResponse
    {
        $kioskToken = Frames::frame()->create($request->validated());

        return to_route('frame.edit', ['frame' => $kioskToken->frame])->with(self::TOKEN_FLASH_KEY, $kioskToken->token);
    }

    public function edit(Frame $frame): Response
    {
        $token = session(self::TOKEN_FLASH_KEY);

        return Inertia::render('Edit', [
            'frame' => Frames::frame()
                ->show($frame)
                ->only('id', 'name', 'search_query', 'sources', 'interval_seconds', 'is_active', 'artworks_count'),
            'kioskUrl' => $token ? route('frame.kiosk.show', ['token' => $token]) : null,
            'sources' => Frames::artwork()->sourceOptions(),
        ]);
    }

    public function update(FrameRequest $request, Frame $frame): RedirectResponse
    {
        Frames::frame()->update($frame, $request->validated());

        return to_route('frame.edit', ['frame' => $frame]);
    }

    public function destroy(Frame $frame): RedirectResponse
    {
        Frames::frame()->destroy($frame);

        return to_route('frame.index');
    }

    public function regenerateToken(Frame $frame): RedirectResponse
    {
        $kioskToken = Frames::frame()->regenerateToken($frame);

        return to_route('frame.edit', ['frame' => $frame])->with(self::TOKEN_FLASH_KEY, $kioskToken->token);
    }

    public function fetch(Frame $frame): RedirectResponse
    {
        Frames::artwork()->queueFetch($frame);

        return to_route('frame.edit', ['frame' => $frame]);
    }
}
