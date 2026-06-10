<?php

namespace Coleus\Music\Models;

use Coleus\Music\Database\Factories\PlaylistFactory;
use Coleus\Music\MusicModelDefaults;
use Coleus\Users\Concerns\HasUser;
use Coleus\Users\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Playlist newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Playlist newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Playlist onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Playlist query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Playlist user($users)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Playlist whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Playlist whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Playlist whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Playlist whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Playlist whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Playlist whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Playlist whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Playlist withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Playlist withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[ScopedBy([UserScope::class])]
class Playlist extends MusicModelDefaults
{
    use HasFactory;
    use HasUser;
    use SoftDeletes;

    protected static function newFactory(): PlaylistFactory
    {
        return PlaylistFactory::new();
    }

    public function tracks(): BelongsToMany
    {
        return $this->belongsToMany(Track::class, config('music.table_prefix').'playlist_track')
            ->withTimestamps();
    }
}
