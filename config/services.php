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

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'default_model' => env('CLAUDE_DEFAULT_MODEL', 'claude-sonnet-4-6'),
        'premium_model' => env('CLAUDE_PREMIUM_MODEL', 'claude-opus-4-7'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Telegram Bot (validation admin)
    |--------------------------------------------------------------------------
    |
    | bot_token : obtenu via @BotFather sur Telegram (commande /newbot).
    |             Format : 1234567890:AAExxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
    | admin_chat_id : ton chat ID Telegram personnel (pour recevoir les
    |             notifications). Obtenu via @userinfobot.
    | webhook_secret : token secret partage dans l'URL du webhook pour
    |             authentifier les requetes Telegram. A generer via :
    |             openssl rand -hex 32
    */

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'admin_chat_id' => env('TELEGRAM_ADMIN_CHAT_ID'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        'enabled' => env('TELEGRAM_ENABLED', false),
    ],

];
