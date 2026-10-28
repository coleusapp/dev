<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Http\Requests\ChangeLockKeyRequest;
use Coleus\Notes\Http\Requests\LockHintRequest;
use Coleus\Notes\Http\Requests\LockKeyRequest;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Models\NoteLockKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * The notes password's public parameters (O12.5 / A7.1): the browser
 * fetches them to turn a typed password into the key, and sends new ones
 * when a password is set or reset.
 */
class LockKeyController extends Controller
{
    /**
     * The key new locks use, or null before a password is set.
     */
    public function current(): JsonResponse
    {
        Gate::authorize('viewAny', Note::class);

        return response()->json(['data' => NoteLockKey::current()?->toClient()]);
    }

    /**
     * Any of the user's keys, to open a note locked with an older password.
     */
    public function show(NoteLockKey $lockKey): JsonResponse
    {
        Gate::authorize('viewAny', Note::class);

        return response()->json(['data' => $lockKey->toClient()]);
    }

    /**
     * The notes locked with a key, encrypted, so the browser can re-encrypt
     * them for a new password (A7.1).
     */
    public function notes(NoteLockKey $lockKey): JsonResponse
    {
        Gate::authorize('viewAny', Note::class);

        return response()->json([
            'data' => Note::query()->withTrashed()->where('lock_key_id', $lockKey->id)->get(['id', 'locked_iv', 'locked_body'])
                ->map(fn (Note $note) => ['id' => $note->id, 'iv' => $note->locked_iv, 'ciphertext' => $note->locked_body])
                ->all(),
        ]);
    }

    /**
     * Changes the current password: the key's parameters and every note
     * locked with it, together, so no note is left locked with a key whose
     * parameters are gone. The browser must send all of them.
     */
    public function update(ChangeLockKeyRequest $request, NoteLockKey $lockKey): JsonResponse
    {
        Gate::authorize('create', Note::class);

        if ($lockKey->retired_at !== null) {
            throw ValidationException::withMessages(['salt' => 'Only the current notes password can be changed.']);
        }

        $notes = Note::query()->withTrashed()->where('lock_key_id', $lockKey->id)->get();
        $reencrypted = $request->reencrypted();

        if ($notes->pluck('id')->sort()->values()->all() !== collect(array_keys($reencrypted))->sort()->values()->all()) {
            throw ValidationException::withMessages(['notes' => 'Every note locked with this password must be sent, re-encrypted. Try again.']);
        }

        DB::transaction(function () use ($request, $lockKey, $notes, $reencrypted) {
            $lockKey->update($request->safe()->except('notes'));

            foreach ($notes as $note) {
                Note::withoutTimestamps(fn () => $note->forceFill(['locked_iv' => $reencrypted[$note->id]['iv'], 'locked_body' => $reencrypted[$note->id]['ciphertext']])->save());
            }
        });

        return response()->json(['data' => $lockKey->refresh()->toClient()]);
    }

    /**
     * Changes the hint shown with the password prompt.
     */
    public function hint(LockHintRequest $request, NoteLockKey $lockKey): JsonResponse
    {
        Gate::authorize('create', Note::class);

        $lockKey->update(['hint' => $request->validated('hint')]);

        return response()->json(['data' => $lockKey->toClient()]);
    }

    /**
     * Sets the password, or resets it: the new key is used for new locks,
     * and notes locked before keep their old password.
     */
    public function store(LockKeyRequest $request): JsonResponse
    {
        Gate::authorize('create', Note::class);

        $key = DB::transaction(function () use ($request) {
            NoteLockKey::query()->whereNull('retired_at')->update(['retired_at' => now()]);

            return NoteLockKey::query()->create($request->validated());
        });

        return response()->json(['data' => $key->toClient()], 201);
    }
}
