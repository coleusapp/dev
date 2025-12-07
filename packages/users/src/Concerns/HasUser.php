<?php

namespace Coleus\Users\Concerns;

use Coleus\Users\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\DB;

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

    /**
     * @throws \Throwable
     */
    public function save(array $options = [])
    {
        return DB::transaction(function () use ($options) {
            $model = parent::save($options);

            $this->users()->sync(auth()->user());

            return $model;
        });
    }

    #[Scope]
    public function user(Builder $query, $users): void
    {
        $query->whereHas('users', fn (Builder $subQuery) => $subQuery
            ->whereIn('users.id', [$users])
        );
    }
}
