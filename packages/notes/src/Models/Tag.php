<?php

namespace Coleus\Notes\Models;

use Coleus\Notes\NotesModelDefaults;
use Coleus\Users\Concerns\HasUser;
use Coleus\Users\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A hashtag, stored lowercased by its full path ("work/meetings").
 * Tags are created and removed by NoteTags as note bodies change.
 *
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Note> $notes
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag user($users)
 *
 * @mixin \Eloquent
 */
#[ScopedBy([UserScope::class])]
class Tag extends NotesModelDefaults
{
    use HasUser;

    protected $fillable = [
        'name',
    ];

    /**
     * @return BelongsToMany<Note, $this>
     */
    public function notes(): BelongsToMany
    {
        return $this->belongsToMany(Note::class, config('notes.table_prefix').'note_tag');
    }
}
