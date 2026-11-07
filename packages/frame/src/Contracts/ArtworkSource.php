<?php

namespace Coleus\Frame\Contracts;

use Coleus\Frame\Data\ArtworkData;
use Illuminate\Support\Collection;

interface ArtworkSource
{
    /** Stored with each artwork and used as its image folder, so it must never change. */
    public function key(): string;

    /** Shown to the user when choosing a frame's sources. */
    public function name(): string;

    /** @return Collection<int, ArtworkData> */
    public function search(?string $query, int $count): Collection;

    /** Returns the raw image bytes; throws when the image can't be downloaded. */
    public function image(ArtworkData $artwork): string;
}
