<?php

namespace Coleus\Frame;

use Coleus\Frame\Services\ArtworkService;
use Coleus\Frame\Services\FrameService;
use Coleus\Frame\Services\KioskService;

class Frames
{
    public function frame(): FrameService
    {
        return app(FrameService::class);
    }

    public function kiosk(): KioskService
    {
        return app(KioskService::class);
    }

    public function artwork(): ArtworkService
    {
        return app(ArtworkService::class);
    }
}
