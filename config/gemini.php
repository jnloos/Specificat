<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Gemini API Key
    |--------------------------------------------------------------------------
    |
    | The API key used to authenticate with the Gemini (Google Generative AI)
    | API. You should store this securely in your .env file and never commit
    | it to version control.
    |
    */

    'api_key' => env('GEMINI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Available Models (Free Tier)
    |--------------------------------------------------------------------------
    |
    | This array lists all models currently accessible in the free version of
    | the Gemini API. You can reference these when making requests to specify
    | which model to use for generating responses.
    |
    */

    'models' => [
        'gemini-1.5-flash' => 'models/gemini-1.5-flash',
        'gemini-1.5-pro' => 'models/gemini-1.5-pro',
        'gemini-pro' => 'models/gemini-pro',
        'gemini-pro-vision' => 'models/gemini-pro-vision',
    ],

];
