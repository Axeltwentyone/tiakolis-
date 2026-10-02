<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Tiakolisé et fière
    'precommandes' => [
        'notification_email' => env('NOTIFICATION_EMAIL'),
        // paiement : le client envoie le montant sur ce numéro Wave, puis sa capture
        'wave_numero' => env('WAVE_NUMERO'),
        // lien de paiement Wave (facultatif) ; {montant} est remplacé par le total de la commande
        'wave_lien' => env('WAVE_LIEN'),
        // numéro WhatsApp de la boutique (par défaut : le numéro Wave)
        'whatsapp_numero' => env('WHATSAPP_NUMERO') ?: env('WAVE_NUMERO'),
    ],

];
