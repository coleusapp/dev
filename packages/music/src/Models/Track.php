<?php

namespace Coleus\Music\Models;

use Coleus\Music\Database\Factories\TrackFactory;
use Coleus\Music\MusicModelDefaults;
use Coleus\Users\Concerns\HasUser;
use Coleus\Users\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $title
 * @property string|null $path
 * @property int $artist_id
 * @property int|null $album_id
 * @property int|null $genre_id
 * @property int|null $duration
 * @property int|null $track_number
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Coleus\Music\Models\Artist $artist
 * @property-read \Coleus\Music\Models\Album|null $album
 * @property-read \Coleus\Music\Models\Genre|null $genre
 * @property-read \Coleus\Users\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Coleus\Music\Models\Playlist> $playlists
 * @property-read int|null $playlists_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Coleus\Users\Models\User> $users
 * @property-read int|null $users_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Track newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Track newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Track onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Track query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Track user($users)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Track whereAlbumId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Track whereArtistId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Track whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Track whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Track whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Track whereGenreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Track whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Track whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Track whereTrackNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Track whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Track whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Track withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Track withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[ScopedBy([UserScope::class])]
class Track extends MusicModelDefaults
{
    use HasFactory;
    use HasUser;
    use SoftDeletes;

    protected static function newFactory(): TrackFactory
    {
        return TrackFactory::new();
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    public function genre(): BelongsTo
    {
        return $this->belongsTo(Genre::class);
    }

    public function playlists(): BelongsToMany
    {
        return $this->belongsToMany(Playlist::class, config('music.table_prefix').'playlist_track')
            ->withTimestamps();
    }
}
