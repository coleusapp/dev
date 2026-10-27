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
];
