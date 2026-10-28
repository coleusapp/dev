<?php

namespace Coleus\Notes\Services;

use Coleus\Notes\Models\Note;
use Coleus\Notes\Models\NoteLockKey;
use Illuminate\Support\Facades\DB;

/**
 * Password-locked notes (O12.5). The browser encrypts a note's text with
 * the key from the user's notes password (AES-GCM, see
 * resources/js/lib/noteCrypto.ts); the server only stores the result:
 *
 * - locked_body / locked_iv: the encrypted text,
 * - lock_key_id: which password it was locked with,
 * - title: kept readable so the note shows in the list (as Apple Notes),
 * - body: emptied, so search, snippets, tags, links and previews see
 *   nothing of it (A7.3).
 *
 * Locking also deletes the note's version history, which holds the
 * readable text.
 */
class NoteLocks
{
    public function lock(Note $note, NoteLockKey $key, string $iv, string $ciphertext, string $title): void
    {
        DB::transaction(function () use ($note, $key, $iv, $ciphertext, $title) {
            $note->versions()->delete();
            $note->forceFill([
                'lock_key_id' => $key->id,
                'locked_iv' => $iv,
                'locked_body' => $ciphertext,
                'title' => $title,
                'body' => '',
            ])->save();
        });
    }

    /**
     * Saves new encrypted text for a locked note (autosave while it's open).
     */
    public function updateLocked(Note $note, string $iv, string $ciphertext, string $title): void
    {
        $note->forceFill(['locked_iv' => $iv, 'locked_body' => $ciphertext, 'title' => $title])->save();
    }

    /**
     * Takes the lock off for good, with the text the browser decrypted.
     */
    public function unlock(Note $note, string $body): void
    {
        $note->forceFill(['lock_key_id' => null, 'locked_iv' => null, 'locked_body' => null, 'body' => $body])->save();
    }
}
