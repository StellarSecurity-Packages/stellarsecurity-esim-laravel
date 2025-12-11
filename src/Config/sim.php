<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Base URL for the Simcard API
    |--------------------------------------------------------------------------
    |
    | This is the base URL of the Simcard API that exposes:
    |   GET  /v1/sim/plans
    |   POST /v1/sim/order
    |   GET  /v1/sim/query/{planId}
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

];
