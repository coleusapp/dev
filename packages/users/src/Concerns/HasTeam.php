<?php

namespace Coleus\Users\Concerns;

use Coleus\Users\Models\Team;
use Coleus\Users\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasTeam
{
    public function teams(): MorphToMany
    {
        return $this->morphToMany(
            Team::class,
            'model',
            'model_has_teams',
            'model_id',
        );
    }
}
