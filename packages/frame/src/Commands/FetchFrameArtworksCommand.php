<?php

namespace Coleus\Frame\Commands;

use Coleus\Frame\Facades\Frames;
use Coleus\Frame\Models\Frame;
use Illuminate\Console\Command;

class FetchFrameArtworksCommand extends Command
{
    protected $signature = 'frame:fetch
                            {frame? : Frame id (defaults to every active frame)}
                            {--count=20 : Artworks to request per frame}';

    protected $description = 'Queue new artworks from the configured artwork source for frames';

    public function handle(): int
    {
        $count = (int) $this->option('count');

        if ($count < 1 || $count > 100) {
            $this->error('The --count option must be between 1 and 100.');

            return self::FAILURE;
        }

        $frameId = (int) $this->argument('frame') ?: null;

        $frames = Frames::frame()->fetchable($frameId);

        if ($frameId && $frames->isEmpty()) {
            $this->error("Frame [{$this->argument('frame')}] not found.");

            return self::FAILURE;
        }

        $frames->each(fn (Frame $frame) => Frames::artwork()->queueFetch($frame, $count));

        $this->info("Queued artwork fetches for {$frames->count()} frame(s).");

        return self::SUCCESS;
    }
}
