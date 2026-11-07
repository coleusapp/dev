<?php

namespace Coleus\Frame\Models;

use Coleus\Frame\Database\Factories\FrameFactory;
use Coleus\Frame\FrameModelDefaults;
use Coleus\Users\Concerns\HasUser;
use Coleus\Users\Models\Scopes\UserScope;
use Coleus\Users\Models\User;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $token_hash
 * @property string|null $search_query
 * @property list<string> $sources ArtworkSource::key() of each source the frame fetches from.
 * @property int $interval_seconds
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $artworks_count
 * @property-read Collection<int, FrameArtwork> $artworks
 * @property-read Collection<int, User> $users
 *
 * @mixin \Eloquent
 */
#[ScopedBy([UserScope::class])]
class Frame extends FrameModelDefaults
{
    /** @uses \Coleus\Frame\Database\Factories\FrameFactory */
    use HasFactory;

    use HasUser;

    protected $fillable = [
        'name',
        'search_query',
        'sources',
        'interval_seconds',
        'is_active',
    ];

    protected $attributes = [
        'interval_seconds' => 60,
        'is_active' => true,
    ];

    public function casts(): array
    {
        return [
            'sources' => 'array',
            'interval_seconds' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<FrameArtwork, $this>
     */
    public function artworks(): HasMany
    {
        return $this->hasMany(FrameArtwork::class);
    }

    protected static function newFactory(): FrameFactory|Factory
    {
        return FrameFactory::new();
    }
}
