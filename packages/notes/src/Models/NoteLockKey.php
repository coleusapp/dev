<?php

namespace Coleus\Notes\Models;

use Coleus\Notes\NotesModelDefaults;
use Coleus\Users\Concerns\HasUser;
use Coleus\Users\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

/**
 * What the browser needs to turn the user's notes password into the key
 * that locks notes (O12.5): PBKDF2 salt and iterations, plus a "verifier"
 * (a known text encrypted with that key) to tell a right password from a
 * wrong one. The password and the key never reach the server.
 *
 * Resetting the password makes a new key and retires this one; notes
 * locked with it stay locked with the old password (A7.1).
 *
 * @property int $id
 * @property string $salt
 * @property int $iterations
 * @property string $verifier_iv
 * @property string $verifier
 * @property string|null $hint
 * @property \Illuminate\Support\Carbon|null $retired_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NoteLockKey query()
 *
 * @mixin \Eloquent
 */
#[ScopedBy([UserScope::class])]
class NoteLockKey extends NotesModelDefaults
{
    use HasUser;

    protected $table = 'lock_keys';

    protected $fillable = [
        'salt',
        'iterations',
        'verifier_iv',
        'verifier',
        'hint',
    ];

    public function casts(): array
    {
        return [
            'iterations' => 'integer',
            'retired_at' => 'datetime',
        ];
    }

    /**
     * The key new locks use: the newest one not retired.
     */
    public static function current(): ?self
    {
        return self::query()->whereNull('retired_at')->latest('id')->first();
    }

    /**
     * @return array{id: int, salt: string, iterations: int, verifier_iv: string, verifier: string, hint: string|null, current: bool}
     */
    public function toClient(): array
    {
        return [
            'id' => $this->id,
            'salt' => $this->salt,
            'iterations' => $this->iterations,
            'verifier_iv' => $this->verifier_iv,
            'verifier' => $this->verifier,
            'hint' => $this->hint,
            'current' => $this->retired_at === null,
        ];
    }
}
