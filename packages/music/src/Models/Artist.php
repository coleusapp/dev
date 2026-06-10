<?php

namespace Coleus\Music\Models;

use Coleus\Music\Database\Factories\ArtistFactory;
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
 * @property string|null $bio
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Coleus\Users\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Coleus\Music\Models\Album> $albums
 * @property-read int|null $albums_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Coleus\Music\Models\Track> $tracks
 * @property-read int|null $tracks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Coleus\Users\Models\User> $users
 * @property-read int|null $users_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artist newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artist newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artist onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artist query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artist user($users)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artist whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artist whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artist whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artist whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artist whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artist whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artist whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artist withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artist withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[ScopedBy([UserScope::class])]
class Artist extends MusicModelDefaults
{
    use HasFactory;
    use HasUser;
    use SoftDeletes;

    protected static function newFactory(): ArtistFactory
    {
        return ArtistFactory::new();
    }

    public function albums(): HasMany
    {
        return $this->hasMany(Album::class);
    }

    public function tracks(): HasMany
    {
        return $this->hasMany(Track::class);
    }
}
