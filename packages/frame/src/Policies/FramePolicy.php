<?php

namespace Coleus\Frame\Policies;

use Coleus\Frame\Models\Frame;
use Illuminate\Contracts\Auth\Authenticatable;

class FramePolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return true;
    }

    public function create(Authenticatable $user): bool
    {
        return true;
    }

    /**
     * Editing, token regeneration and "fetch now" all count as updating.
     */
    public function update(Authenticatable $user, Frame $frame): bool
    {
        return $frame->isOwnedBy((int) $user->getAuthIdentifier());
    }

    public function delete(Authenticatable $user, Frame $frame): bool
    {
        return $frame->isOwnedBy((int) $user->getAuthIdentifier());
    }
}
