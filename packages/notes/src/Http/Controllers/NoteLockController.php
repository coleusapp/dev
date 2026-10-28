<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Http\Requests\LockedBodyRequest;
use Coleus\Notes\Http\Requests\NoteRequest;
use Coleus\Notes\Http\Resources\NoteDetailResource;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Services\NoteLocks;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Locking a note with the notes password, saving it while it's open, and
 * taking the lock off (O12.5). The text arrives already encrypted.
 */
class NoteLockController extends Controller
{
    public function __construct(private readonly NoteLocks $locks)
    {
        //
    }

    public function store(LockedBodyRequest $request, Note $note): NoteDetailResource
    {
        Gate::authorize('edit', $note);

        $key = $request->lockKey();

        if ($key === null || $key->retired_at !== null) {
            throw ValidationException::withMessages(['key_id' => 'Lock notes with your current notes password.']);
        }

        if ($note->isLocked()) {
            throw ValidationException::withMessages(['key_id' => 'This note is already locked.']);
        }

        $this->locks->lock($note, $key, $request->string('iv')->toString(), $request->string('ciphertext')->toString(), (string) $request->input('title', ''));

        return new NoteDetailResource($note->refresh());
    }

    public function update(LockedBodyRequest $request, Note $note): NoteDetailResource
    {
        Gate::authorize('edit', $note);
        abort_unless($note->isLocked(), 409, 'This note is not locked.');

        $this->locks->updateLocked($note, $request->string('iv')->toString(), $request->string('ciphertext')->toString(), (string) $request->input('title', ''));

        return new NoteDetailResource($note->refresh());
    }

    /**
     * Removes the lock, keeping the text the browser decrypted.
     */
    public function destroy(NoteRequest $request, Note $note): NoteDetailResource
    {
        Gate::authorize('edit', $note);
        abort_unless($note->isLocked(), 409, 'This note is not locked.');

        $this->locks->unlock($note, (string) ($request->noteData()['body'] ?? ''));

        return new NoteDetailResource($note->refresh());
    }
}
