<?php

namespace Coleus\Frame\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Frame\Facades\Frames;
use Coleus\Frame\Models\Frame;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KioskController extends Controller
{
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

        $artwork = Frames::kiosk()->next($frame, $request->integer('current') ?: null);

        if (!$artwork) {
            return response()->noContent();
        }

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
        $artwork = Frames::kiosk()->artwork($this->frameOrFail($token), $artwork) ?? $this->notFound();

        return Storage::disk(config('frame.disk'))->response($artwork->path, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=31536000, immutable',
        ]);
    }

    private function frameOrFail(string $token): Frame
    {
        return Frames::kiosk()->frame($token) ?? $this->notFound();
    }

    private function notFound(): never
    {
        abort(response('Not Found', 404, ['Content-Type' => 'text/plain']));
    }
}
