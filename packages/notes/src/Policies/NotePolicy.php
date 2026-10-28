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

    /**
     * Notes in the trash are read-only until restored.
     */
    public function update(Authenticatable $user, Note $note): bool
    {
        return ! $note->trashed() && $note->isOwnedBy((int) $user->getAuthIdentifier());
    }

    /**
     * Changing what a note says (typing, ticking a checklist item, restoring
     * a version): also not while it's locked read-only (B8.7). Filing it
     * (pin, archive, folder, the lock itself) only needs update.
     */
    public function edit(Authenticatable $user, Note $note): bool
    {
        return $note->read_only_at === null && $this->update($user, $note);
    }

    /**
     * Deleting moves a note to the trash.
     */
    public function delete(Authenticatable $user, Note $note): bool
    {
        return ! $note->trashed() && $note->isOwnedBy((int) $user->getAuthIdentifier());
    }

    public function restore(Authenticatable $user, Note $note): bool
    {
        return $note->trashed() && $note->isOwnedBy((int) $user->getAuthIdentifier());
    }

    public function forceDelete(Authenticatable $user, Note $note): bool
    {
        return $note->trashed() && $note->isOwnedBy((int) $user->getAuthIdentifier());
    }
}
