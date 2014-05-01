<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Envelope Key
    |--------------------------------------------------------------------------
    |
    | The key used to wrap data in the envelope() macro response.
    |
    */
    'envelope_key' => 'data',

    /*
    |--------------------------------------------------------------------------
    | Meta Key
    |--------------------------------------------------------------------------
    |
    | The key used to nest metadata in the envelope() macro response.
    |
    */
    'meta_key' => 'meta',

    /*
    |--------------------------------------------------------------------------
    | Include Status Code
    |--------------------------------------------------------------------------
    |
    | When enabled, the HTTP status code will be included in the JSON body
    | of every macro response under the "status" key.
    |
    */
    'include_status_code' => true,
];
