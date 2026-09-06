<?php

namespace Coleus\Settings\Models;

use Coleus\Apps\Concerns\HasApp;
use Coleus\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Settings extends Model
{
    use HasApp;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

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
