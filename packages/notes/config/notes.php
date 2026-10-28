<?php

return [
    /*
     * Table prefix to be used when running the migration classes.
     *
     * Default: notes_
     */

    'table_prefix' => 'notes_',

    /*
     * Route prefix for this package. Everything is going to be prefixed by this value.
     *
     * Default: notes
     */
    'route_prefix' => 'notes',

    /*
     * Settings group prefix.
     *
     * Default: notes
     */
    'settings_prefix' => 'notes',

    /*
     * Notes stay in the trash this many days before notes:purge-trash
     * deletes them for good.
     *
     * Default: 30
     */
    'trash_retention_days' => 30,

    /*
     * Version history. Saves within interval_minutes of the latest
     * unnamed version update it instead of adding a new one; only the
     * newest `keep` unnamed versions are kept (named ones always are).
     *
     * Default: 5 minutes, 100 versions
     */
    'versions' => [
        'interval_minutes' => 5,
        'keep' => 100,
    ],

    /*
     * Where an "@2026-10-28" date chip in a note links to, with {date} as
     * YYYY-MM-DD, e.g. a calendar day view. Null: chips don't link.
     *
     * Default: null
     */
    'calendar_url' => env('NOTES_CALENDAR_URL'),

    /*
     * Attachments (images, video, audio, documents in notes). Files go on
     * this disk under notes/attachments and are only served through the
     * notes routes, to their owner. Sizes are per kind, in kilobytes; PHP's
     * upload_max_filesize / post_max_size must allow the biggest.
     *
     * Default: the private local disk; 20 MB images, 500 MB video,
     * 100 MB audio, 50 MB documents
     */
    'attachments' => [
        'disk' => env('NOTES_ATTACHMENTS_DISK', 'local'),
        'max_kb' => [
            'image' => 20 * 1024,
            'video' => 500 * 1024,
            'audio' => 100 * 1024,
            'document' => 50 * 1024,
        ],
    ],
];
