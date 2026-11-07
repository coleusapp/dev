<?php

namespace Coleus\Frame\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Frame\Models\Frame;
use Coleus\Frame\Models\FrameArtwork;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The stateless pages a physical frame polls. The token in the URL is the
 * only credential; an unknown token or an inactive frame is a plain 404.
 */
class KioskController extends Controller
{
    /**
     * Rotation picks randomly among this many least recently shown artworks,
     * so every artwork comes round without the order being predictable.
     */
    public const int ROTATION_WINDOW = 20;

    public function show(string $token): View
    {
        $this->frameOrFail($token);

        return view('frame::kiosk', [
            'nextUrl' => route('frame.kiosk.next', ['token' => $token], false),
        ]);
    }

    public function next(Request $request, string $token): JsonResponse|Response
    {
        $frame = $this->frameOrFail($token);
        $currentId = $request->integer('current') ?: null;

        $artwork = $this->pickNext($frame, $currentId) ?? ($currentId ? $this->pickNext($frame, null) : null);

        if ($artwork === null) {
            return response()->noContent();
        }

        $artwork->forceFill(['last_shown_at' => now()])->save();

        return response()->json([
            'id' => $artwork->id,
            'image_url' => route('frame.kiosk.image', ['token' => $token, 'artwork' => $artwork->id], false),
            'title' => $artwork->title,
            'artist' => $artwork->artist_display,
            'date' => $artwork->date_display,
            'interval_seconds' => $frame->interval_seconds,
        ]);
    }

    public function image(string $token, int $artwork): StreamedResponse
    {
        $artwork = $this->frameOrFail($token)->artworks()->find($artwork) ?? $this->notFound();
        $disk = Storage::disk(config('frame.disk'));

        if (! $disk->exists($artwork->path)) {
            $this->notFound();
        }

        return $disk->response($artwork->path, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=31536000, immutable',
        ]);
    }

    private function pickNext(Frame $frame, ?int $excludeId): ?FrameArtwork
    {
        $candidates = $frame->artworks()
            ->when($excludeId, fn ($query) => $query->whereKeyNot($excludeId))
            ->orderByRaw('CASE WHEN last_shown_at IS NULL THEN 0 ELSE 1 END')
            ->orderBy('last_shown_at')
            ->orderBy('id')
            ->limit(self::ROTATION_WINDOW)
            ->get();

        return $candidates->isEmpty() ? null : $candidates->random();
    }

    private function frameOrFail(string $token): Frame
    {
        return Frame::findActiveByToken($token) ?? $this->notFound();
    }

    private function notFound(): never
    {
        abort(response('Not Found', 404, ['Content-Type' => 'text/plain']));
    }
}
