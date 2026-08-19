<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Brevo API Key
    |--------------------------------------------------------------------------
    |
    | Transactional API key from the Brevo dashboard (Settings > SMTP & API >
    | API Keys). Sent as the `api-key` header on every request.
    |
    */
    'api_key' => env('BREVO_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Brevo API Base URI
    |--------------------------------------------------------------------------
    */
    'base_uri' => env('BREVO_API_BASE_URI', 'https://api.brevo.com/v3/'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Seconds to wait for a response from the Brevo API before the transport
    | throws.
    |
    */
    'timeout' => env('BREVO_API_TIMEOUT', 15),

];
