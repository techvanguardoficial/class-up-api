<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reverb Configuration
    |--------------------------------------------------------------------------
    */

    'debug' => env('REVERB_DEBUG', false),

    'servers' => [
        'reverb' => [
            'host' => env('REVERB_SERVER_HOST', '0.0.0.0'),
            'port' => env('REVERB_SERVER_PORT', 8080),
            'hostname' => env('REVERB_SERVER_HOSTNAME'),
            'scaling' => [
                'enabled' => env('REVERB_SCALING_ENABLED', false),
                'channel' => env('REVERB_SCALING_CHANNEL', 'reverb'),
                'server' => [],
            ],
        ],
    ],

    'applications' => [
        [
            'id' => env('REVERB_APP_ID', 'your-app-id'),
            'name' => env('APP_NAME'),
            'key' => env('REVERB_APP_KEY', 'your-app-key'),
            'secret' => env('REVERB_APP_SECRET', 'your-app-secret'),
            'max_connections' => 10000,
            'allowed_origins' => explode(',', env('REVERB_ALLOWED_ORIGINS', 'localhost,localhost:3000,127.0.0.1:3000')),
            'disable_client_events' => false,
        ],
    ],

];
