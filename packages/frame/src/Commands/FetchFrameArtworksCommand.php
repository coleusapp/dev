<?php

namespace Coleus\Frame\Commands;

use Coleus\Frame\Jobs\FetchFrameArtworks;
use Coleus\Frame\Models\Frame;
use Coleus\Users\Models\Scopes\UserScope;
use Illuminate\Console\Command;

/**
 * Queues an artwork fetch for one frame (any state) or for every active frame.
 * Runs without an authenticated user, so the per-user scope is bypassed.
 */
class FetchFrameArtworksCommand extends Command
{
    protected $signature = 'frame:fetch
                            {frame? : Frame id (defaults to every active frame)}
                            {--count=20 : Artworks to request per frame}';

    protected $description = 'Queue new artworks from the Art Institute of Chicago for frames';

    public function handle(): int
    {
        $count = (int) $this->option('count');

        if ($count < 1 || $count > 100) {
            $this->error('The --count option must be between 1 and 100.');

            return self::FAILURE;
        }

        $query = Frame::query()->withoutGlobalScope(UserScope::class);

        if ($this->argument('frame') !== null) {
            $frames = $query->whereKey($this->argument('frame'))->get();

            if ($frames->isEmpty()) {
                $this->error("Frame [{$this->argument('frame')}] not found.");

                return self::FAILURE;
            }
        } else {
            $frames = $query->where('is_active', true)->get();
        }

        $frames->each(fn (Frame $frame) => FetchFrameArtworks::dispatch($frame, $count));

        $this->info("Queued artwork fetches for {$frames->count()} frame(s).");

        return self::SUCCESS;
    }
}
