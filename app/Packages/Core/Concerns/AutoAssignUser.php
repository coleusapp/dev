<?php

namespace App\Packages\Core\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \App\Models\User
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
