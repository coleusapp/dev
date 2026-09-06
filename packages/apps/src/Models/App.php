<?php

namespace Coleus\Apps\Models;

use Coleus\Apps\Exceptions\AppNameNotDefined;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * @property string|null $name
 */
class App extends Model
{
    protected ?string $name = null;

    protected $table = 'apps';

    protected $fillable = ['name'];

    /**
     * @throws Throwable
     */
    public function get(): static
    {
        throw_unless($this->name, AppNameNotDefined::class);

        return static::firstOrCreate(['name' => $this->name]);
    }
}
