<?php

namespace Coleus\Users\Concerns;

use Coleus\Users\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasUser
{
    public function users(): MorphToMany
    {
        return $this->morphToMany(
            User::class,
            'model',
            'model_has_users',
            'model_id',
        );
    }
}
