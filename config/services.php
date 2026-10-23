<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

'twilio' => [
    'sid' => env('TWILIO_SID'),
    'token' => env('TWILIO_AUTH_TOKEN'),
    'from' => env('TWILIO_FROM'),
],

'moodle' => [
        'key' => env('MOODLE_SHARED_KEY'),
        'iv'  => env('MOODLE_SHARED_IV'),
    ],

// COE: OTP SMS the faculty confirms a question paper freeze with. Needs its own
// DLT-approved template; `message` is that exact body with {#var#} for the OTP.
// Unset: local/testing write the OTP to the log, other environments refuse.
'coe_freeze_otp' => [
    'dlt_template_id' => env('COE_FREEZE_OTP_DLT_TEMPLATE_ID'),
    'message' => env('COE_FREEZE_OTP_MESSAGE'),
],

];
