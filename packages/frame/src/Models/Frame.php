<?php

namespace Coleus\Frame\Models;

use Coleus\Frame\Database\Factories\FrameFactory;
use Coleus\Frame\FrameModelDefaults;
use Coleus\Users\Concerns\HasUser;
use Coleus\Users\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A physical art frame. Its kiosk URL carries a secret token; only the
 * SHA-256 hash of that token is stored (see hashToken).
 *
 * @property int $id
 * @property string $name
 * @property string $token_hash
 * @property string|null $search_query
 * @property int $interval_seconds
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, FrameArtwork> $artworks
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Coleus\Users\Models\User> $users
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

    /**
     * Folder on the frame.disk disk holding this frame's resized artworks.
     */
    public function artworkDirectory(): string
    {
        return "frame/{$this->id}";
    }

    public function isOwnedBy(int $userId): bool
    {
        return $this->users()->whereKey($userId)->exists();
    }

    /**
     * Replaces the kiosk token and returns the new plain token, which is
     * never stored and so can be shown to the owner only this once.
     */
    public function regenerateToken(): string
    {
        $token = static::generateToken();

        $this->forceFill(['token_hash' => static::hashToken($token)])->save();

        return $token;
    }

    /**
     * The kiosk has no logged-in user, so the per-user scope is bypassed;
     * the secret token is the only credential.
     */
    public static function findActiveByToken(string $token): ?self
    {
        return static::query()
            ->withoutGlobalScope(UserScope::class)
            ->where('token_hash', static::hashToken($token))
            ->where('is_active', true)
            ->first();
    }

    public static function generateToken(): string
    {
        return Str::random(48);
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    protected static function booted(): void
    {
        static::deleted(function (Frame $frame): void {
            Storage::disk(config('frame.disk'))->deleteDirectory($frame->artworkDirectory());
        });
    }

    protected static function newFactory(): FrameFactory|Factory
    {
        return FrameFactory::new();
    }
}
