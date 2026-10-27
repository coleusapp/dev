<?php

namespace Coleus\Notes\Models;

use Coleus\Notes\NotesModelDefaults;
use Coleus\Users\Concerns\HasUser;
use Coleus\Users\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A folder of notes. Folders nest through parent_id; a note sits in at
 * most one folder (notes without one are at the top level).
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Folder|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Folder> $children
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Note> $notes
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Folder query()
 *
 * @mixin \Eloquent
 */
#[ScopedBy([UserScope::class])]
class Folder extends NotesModelDefaults
{
    use HasUser;

    public const int MAX_NAME_LENGTH = 100;

    protected $fillable = [
        'name',
    ];

    /**
     * @return BelongsTo<Folder, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    /**
     * @return HasMany<Folder, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    /**
     * @return HasMany<Note, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }
}
