<?php

namespace Coleus\Frame\Models;

use Coleus\Frame\Database\Factories\FrameArtworkFactory;
use Coleus\Frame\FrameModelDefaults;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $frame_id
 * @property string $source ArtworkSource::key() of the source it came from.
 * @property int $source_id The artwork's id in that source.
 * @property string $title
 * @property string|null $artist_display
 * @property string|null $date_display
 * @property string $path
 * @property Carbon|null $last_shown_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Frame $frame
 *
 * @mixin \Eloquent
 */
class FrameArtwork extends FrameModelDefaults
{
    /** @uses \Coleus\Frame\Database\Factories\FrameArtworkFactory */
    use HasFactory;

    public function casts(): array
    {
        return [
            'source_id' => 'integer',
            'last_shown_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Frame, $this>
     */
    public function frame(): BelongsTo
    {
        return $this->belongsTo(Frame::class);
    }

    protected static function newFactory(): FrameArtworkFactory|Factory
    {
        return FrameArtworkFactory::new();
    }
}
