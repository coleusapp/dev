<?php

namespace Coleus\Music;

use Coleus\Music\Services\AlbumService;
use Coleus\Music\Services\ArtistService;
use Coleus\Music\Services\GenreService;
use Coleus\Music\Services\PlaylistService;
use Coleus\Music\Services\TrackService;

class Music
{
    public static function album(): AlbumService
    {
        return new AlbumService;
    }

    public static function artist(): ArtistService
    {
        return new ArtistService;
    }

    public static function genre(): GenreService
    {
        return new GenreService;
    }

    public static function playlist(): PlaylistService
    {
        return new PlaylistService;
    }

    public static function track(): TrackService
    {
        return new TrackService;
    }
}
