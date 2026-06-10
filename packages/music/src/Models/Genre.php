<?php

namespace Coleus\Music\Models;

use Coleus\Music\Database\Factories\GenreFactory;
use Coleus\Music\MusicModelDefaults;
use Coleus\Users\Concerns\HasUser;
use Coleus\Users\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Coleus\Users\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Coleus\Music\Models\Track> $tracks
 * @property-read int|null $tracks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Coleus\Users\Models\User> $users
 * @property-read int|null $users_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Genre newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Genre newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Genre onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Genre query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Genre user($users)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Genre whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Genre whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Genre whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Genre whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Genre whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Genre whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Genre withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Genre withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[ScopedBy([UserScope::class])]
class Genre extends MusicModelDefaults
{
    use HasFactory;
    use HasUser;
    use SoftDeletes;

    protected static function newFactory(): GenreFactory
    {
        return GenreFactory::new();
    }

    public function tracks(): HasMany
    {
        return $this->hasMany(Track::class);
    }
}
