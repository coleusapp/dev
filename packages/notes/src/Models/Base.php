<?php

namespace Coleus\Notes\Models;

use Coleus\Notes\NotesModelDefaults;
use Coleus\Users\Concerns\HasUser;
use Coleus\Users\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

/**
 * A base (O11.1, Obsidian's Bases): a saved table over notes. Which notes,
 * which columns (properties, note fields, formulas), filters and sort live
 * in the definition (Bases\BaseDefinition); Bases\BaseTable builds the rows.
 *
 * @property int $id
 * @property string $name
 * @property array<string, mixed> $definition
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Base query()
 *
 * @mixin \Eloquent
 */
#[ScopedBy([UserScope::class])]
class Base extends NotesModelDefaults
{
    use HasUser;

    protected $fillable = [
        'name',
        'definition',
    ];

    public function casts(): array
    {
        return [
            'definition' => 'array',
        ];
    }
}
