<?php

namespace Coleus\Notes\Models;

use Coleus\Notes\NotesModelDefaults;
use Coleus\Users\Concerns\HasUser;
use Coleus\Users\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

/**
 * A canvas (O10.1, Obsidian's Canvas): cards on an endless board, joined
 * by arrows. data is JSON Canvas (jsoncanvas.org) as Canvas\JsonCanvas
 * keeps it: note and attachment cards also carry the id they point to.
 *
 * @property int $id
 * @property string $name
 * @property array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>} $data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Canvas query()
 *
 * @mixin \Eloquent
 */
#[ScopedBy([UserScope::class])]
class Canvas extends NotesModelDefaults
{
    use HasUser;

    protected $fillable = [
        'name',
        'data',
    ];

    public function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }
}
