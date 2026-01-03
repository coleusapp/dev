<?php

namespace Coleus\Settings\Concerns;

use Coleus\Settings\Models\Settings;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\DB;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasSettings
{
    public function settings(): MorphToMany
    {
        return $this->morphToMany(
            Settings::class,
            'model',
            'model_has_settings',
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

            $this->settings()->sync(auth()->user());

            return $model;
        });
    }

    #[Scope]
    public function setting(Builder $query, $settings): void
    {
        $query->whereHas('settings', fn (Builder $subQuery) => $subQuery
            ->whereIn('users.id', [$settings])
        );
    }
}
