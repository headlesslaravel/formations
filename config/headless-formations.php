<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Response mode for formation
    |--------------------------------------------------------------------------
    | When using Route::formation to use built in controllers,
    | you can control how the behavior of the responses.
    | the available options are: blade, api, inertia
    |
    */
    'mode' => 'blade',

    /*
    |--------------------------------------------------------------------------
    | Export settings
    |--------------------------------------------------------------------------
    | Customize the following options when using formation exports.
    | Things like the date format and file format used for the
    | file download which can be overridden with exportAs()
    |
    */
    'exports' => [

        'date_format' => 'Y-m-d_h:i:s',

        'file_format' => 'xlsx',
    ],
];
