<?php

namespace Coleus\Support\Concerns;

use Coleus\Users\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \Coleus\Users\Models\User
 */
trait AutoAssignUser
{
    public function save(array $options = [])
    {
        $this->user_id = auth()->id();

        return parent::save($options);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
