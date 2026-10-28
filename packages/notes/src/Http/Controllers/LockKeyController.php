<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Http\Requests\LockKeyRequest;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Models\NoteLockKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

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
