<?php

namespace Coleus\Frame\Services;

use Coleus\Frame\Models\Frame;
use Coleus\Frame\Models\FrameArtwork;
use Coleus\Users\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KioskService
{
    public const int RotationWindow = 20;

    public function generateToken(): string
    {
        return Str::random(48);
    }

    public function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function frame(string $token): ?Frame
    {
        return Frame::query()
            ->withoutGlobalScope(UserScope::class)
            ->where('token_hash', $this->hashToken($token))
            ->where('is_active', true)
            ->first();
    }

    public function next(Frame $frame, ?int $currentArtworkId): ?FrameArtwork
    {
        $artwork = $this->pick($frame, $currentArtworkId) ?? ($currentArtworkId ? $this->pick($frame, null) : null);

        if (!$artwork) {
            return null;
        }

        $artwork->forceFill(['last_shown_at' => now()])->save();

        return $artwork;
    }

    public function artwork(Frame $frame, int $artworkId): ?FrameArtwork
    {
        $artwork = $frame->artworks()->find($artworkId);

        if (!$artwork || !Storage::disk(config('frame.disk'))->exists($artwork->path)) {
            return null;
        }

        return $artwork;
    }

    private function pick(Frame $frame, ?int $excludedArtworkId): ?FrameArtwork
    {
        $candidates = $frame->artworks()
            ->when($excludedArtworkId, fn (Builder $query) => $query->whereKeyNot($excludedArtworkId))
            ->orderByRaw('case when last_shown_at is null then 0 else 1 end')
            ->orderBy('last_shown_at')
            ->orderBy('id')
            ->limit(self::RotationWindow)
            ->get();

        return $candidates->isEmpty() ? null : $candidates->random();
    }
}
