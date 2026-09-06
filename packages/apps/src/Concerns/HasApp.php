<?php

namespace Coleus\Apps\Concerns;

use Coleus\Apps\Models\App;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @mixin Model
 */
trait HasApp
{
    public function apps(): MorphToMany
    {
        return $this->morphToMany(
            App::class,
            'model',
            'model_has_apps',
            'model_id',
        );
    }

    #[Scope]
    public function app(Builder $query, $apps): void
    {
        $query->whereHas('apps', fn (Builder $subQuery) => $subQuery
            ->whereIn('apps.id', [$apps])
        );
    }
}
