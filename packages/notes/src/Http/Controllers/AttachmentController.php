<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Http\Requests\AttachmentRequest;
use Coleus\Notes\Models\Attachment;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Services\Attachments;
use Coleus\Notes\Services\AttachmentUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
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

    /**
     * The attachment manager (O3.5): every file, grouped by kind, with the
     * notes that use it.
     */
    public function index(AttachmentUsage $usage): InertiaResponse
    {
        Gate::authorize('viewAny', Note::class);

        $attachments = Attachment::query()->latest('id')->get();
        $users = $usage->notesUsing($attachments);

        return Inertia::render('Attachments', [
            'attachments' => $attachments->map(fn (Attachment $attachment) => [
                ...$attachment->toClient(),
                'width' => $attachment->width,
                'height' => $attachment->height,
                'duration_ms' => $attachment->duration_ms,
                'poster' => $attachment->poster_path !== null,
                'created_at' => $attachment->created_at?->toIso8601String(),
                'notes' => $users[$attachment->uuid] ?? [],
            ])->values()->all(),
            'hasLockedNotes' => Note::query()->withTrashed()->whereNotNull('lock_key_id')->exists(),
        ]);
    }

    /**
     * Deletes an attachment no note uses (files in use are refused, so a
     * note never loses a picture by accident).
     */
    public function destroy(Attachment $attachment, AttachmentUsage $usage): RedirectResponse
    {
        Gate::authorize('create', Note::class);

        if (($usage->notesUsing(collect([$attachment]))[$attachment->uuid] ?? []) !== []) {
            throw ValidationException::withMessages(['attachment' => '“'.$attachment->name.'” is used in a note; remove it there first.']);
        }

        $usage->delete($attachment);

        return back()->with('status', 'Attachment deleted.');
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
