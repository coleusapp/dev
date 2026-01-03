<?php

namespace Coleus\Support\Models;

use Coleus\Support\Exceptions\AppNameNotDefined;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string|null $name
 */
class App extends Model
{
    protected ?string $name = null;

    protected $table = 'apps';

    /**
     * @throws \Throwable
     */
    public function get(): static
    {
        throw_unless($this->name, AppNameNotDefined::class);

        return static::whereName($this->name)->firstOrFail();
    }
}
