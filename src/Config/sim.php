<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Base URL for the Simcard API
    |--------------------------------------------------------------------------
    |
    | The URL should normally include the API prefix, for example:
    | https://sim-api.example.com/api
    |
    */

    'base_url' => env('SIM_API_BASE_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Basic auth credentials for the Simcard API
    |--------------------------------------------------------------------------
    */

    'username' => env('SIM_API_USERNAME', ''),
    'password' => env('SIM_API_PASSWORD', ''),

    /*
    |--------------------------------------------------------------------------
    | HTTP client
    |--------------------------------------------------------------------------
    */

    'timeout' => (int) env('SIM_API_TIMEOUT', 35),
    'connect_timeout' => (int) env('SIM_API_CONNECT_TIMEOUT', 20),
    'request_id_header' => env('SIM_API_REQUEST_ID_HEADER', 'X-Request-ID'),

];
