<?php

namespace Coleus\Notes\Policies;

use Coleus\Notes\Models\Note;
use Illuminate\Contracts\Auth\Authenticatable;

class NotePolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return true;
    }

    public function view(Authenticatable $user, Note $note): bool
    {
        return $note->isOwnedBy((int) $user->getAuthIdentifier());
    }

    public function create(Authenticatable $user): bool
    {
        return true;
    }

    public function update(Authenticatable $user, Note $note): bool
    {
        return $note->isOwnedBy((int) $user->getAuthIdentifier());
    }

    public function delete(Authenticatable $user, Note $note): bool
    {
        return $note->isOwnedBy((int) $user->getAuthIdentifier());
    }
}
