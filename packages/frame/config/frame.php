<?php

return [

    /*
     * Prefix for the Frame tables (frame_frames, frame_artworks).
     *
     * Default: frame_
     */
    'table_prefix' => 'frame_',

    /*
     * The URL prefix of the kiosk and management pages.
     *
     * Default: frame
     */
    'route_prefix' => 'frame',

    /*
     * Per-IP throttle on the kiosk pages, as "requests,minutes".
     *
     * Default: 120,1
     */
    'kiosk_rate_limit' => '120,1',

    /*
     * Art Institute of Chicago API and IIIF image server.
     */
    'aic' => [
        'api_url' => 'https://api.artic.edu/api/v1',
        'iiif_url' => 'https://www.artic.edu/iiif/2',
        'user_agent' => 'coleus-frame (personal project)',
    ],

    /*
     * Largest size a stored artwork is fitted into (never cropped or upscaled).
     */
    'display' => [
        'width' => 1920,
        'height' => 1080,
    ],

    /*
     * Private disk the resized artworks are stored on.
     *
     * Default: local
     */
    'disk' => 'local',

    /*
     * Artworks kept per frame; the oldest are pruned after each fetch.
     *
     * Default: 200
     */
    'pool_size' => 200,
];
