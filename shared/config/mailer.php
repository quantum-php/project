<?php

return [
    /**
     * ---------------------------------------------------------
     * Mailer settings
     * ---------------------------------------------------------
     *
     * Default configuration to use.
     */
    'default' => 'smtp',

    'mail_trap' => true,

    /**
     * The number of transport log lines the mailer keeps: the SMTP exchange in debug mode
     * and the errors of failed sends. 0 keeps every line.
     */
    'transport_log_limit' => 1000,

    'smtp' => [
        'host' => env('MAIL_HOST'),
        'secure' => env('MAIL_SMTP_SECURE'),
        'port' => env_int('MAIL_PORT', 25),
        'username' => env('MAIL_USERNAME'),
        'password' => env('MAIL_PASSWORD'),
    ],

    'sendinblue' => [
        'api_key' => env('SENDINBLUE_APIKEY', null),
    ],

    'sendgrid' => [
        'api_key' => env('SENDGRID_APIKEY', null),
    ],

    'mailgun' => [
        'api_key' => env('MAILGUN_APIKEY', null),
        'domain' => env('MAILGUN_DOMAIN', null),
    ],

    'mandrill' => [
        'api_key' => env('MANDRILL_APIKEY', null),
    ],

    'resend' => [
        'api_key' => env('RESEND_APIKEY', null),
    ],
];
