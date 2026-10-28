<?php

namespace Coleus\Notes\Models;

use Coleus\Notes\NotesModelDefaults;
use Coleus\Users\Concerns\HasUser;
use Coleus\Users\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

/**
 * A saved set of rules (Apple Notes' Smart Folder). Notes aren't stored in
 * it; it lists whatever matches the rules (Search\SmartFolderRules).
 *
 * @property int $id
 * @property string $name
 * @property array<string, mixed> $rules
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmartFolder query()
 *
 * @mixin \Eloquent
 */
#[ScopedBy([UserScope::class])]
class SmartFolder extends NotesModelDefaults
{
    use HasUser;

    protected $fillable = [
        'name',
        'rules',
    ];

    public function casts(): array
    {
        return [
            'rules' => 'array',
        ];
    }
}
