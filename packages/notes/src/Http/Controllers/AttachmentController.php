<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Http\Requests\AttachmentRequest;
use Coleus\Notes\Models\Attachment;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Services\Attachments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uploading files into notes and serving them back to their owner (O3.1).
 * Other users get a 404: the user scope never finds their attachments.
 */
class AttachmentController extends Controller
{
    public function __construct(private readonly Attachments $attachments)
    {
        //
    }

    public function store(AttachmentRequest $request): JsonResponse
    {
        Gate::authorize('create', Note::class);

        $attachment = $this->attachments->store($request->file('file'));

        return response()->json(['data' => $attachment->toClient()], 201);
    }

    /**
     * The file, a smaller copy of an image for ?w= (one of the configured
     * widths; the original when there's no such copy), or a video's poster
     * frame for ?poster=1 (404 until there is one).
     */
    public function show(Request $request, Attachment $attachment): Response
    {
        Gate::authorize('viewAny', Note::class);

        $width = $request->integer('w');

        return $this->attachments->response($attachment, $width > 0 ? $width : null, $request->boolean('poster'));
    }
}
