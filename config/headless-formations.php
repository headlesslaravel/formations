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

    'exports' => [

        'date_format' => 'Ymd_his',

        /*
        |--------------------------------------------------------------------------
        | Default file export format
        |--------------------------------------------------------------------------
        | Check here for possible values https://docs.laravel-excel.com/3.1/exports/export-formats.html
        |
        */
        'file_format' => 'xlsx',

    ]
];
