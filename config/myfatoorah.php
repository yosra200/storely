<?php

return [

    'api_key' => env('MYFATOORAH_API_KEY'),

    'base_url' => env(
        'MYFATOORAH_BASE_URL',
        'https://apitest.myfatoorah.com'
    ),

    'payment_method_id' => env(
        'MYFATOORAH_PAYMENT_METHOD_ID'
    ),

    'callback_url' => env(
        'MYFATOORAH_CALLBACK_URL'
    ),

    'error_url' => env(
        'MYFATOORAH_ERROR_URL'
    ),

    'webhook_url' => env(
        'MYFATOORAH_WEBHOOK_URL'
    ),

];
