<?php

// You can place your custom package configuration in here.
return [
    /*
     * The Merchant ID
     *
     * You need to contact BillDesk to request your id.
     */
    'merchant_id' => env('BILLDESK_MERCHANT_ID'),

    /*
     * The Merchant Client ID
     *
     * You need to contact BillDesk to request client id.
     */
    'client_id' => env('BILLDESK_CLIENT_ID'),

    /*
     * The merchant HMAC Key
     *
     * You need to contact BillDesk to request HMAC Key.
     */
    'hmac_key' => env('BILLDESK_HMAC_KEY'),

    // The UAT Prefix to identify test transactions
    'uat_prefix' => env('BILLDESK_UAT_PREFIX', ''),

    // The UAT Prefix to identify test transactions
    'merchant_logo' => env('BILLDESK_MERCHANT_LOGO'),

    /*
     * The response url path without the domain and scheme
     *
     * Example: billdesk/payment/callback
     */
    'response_path' => env('BILLDESK_TRANSACTION_RESPONSE_PATH', 'billdesk/payment/callback'),

    /*
     * The mandate response url path without the domain and scheme
     *
     * Example: billdesk/mandate/callback
     */
    'mandate_response_path' => env('BILLDESK_MANDATE_RESPONSE_PATH', 'billdesk/mandate/callback'),

    /*
     * The indirect url path without the domain and scheme
     *
     * Example: billdesk/payment/webhook
     */
    'webhook_path' => env('BILLDESK_WEBHOOK_PATH', 'billdesk/payment/webhook'),

    // Middleware
    'middleware' => ['web'],

    // Date Format
    'date_format' => 'c',

    // Recurrence Rule
    'recurrence_rule' => 'on',

    // Item Code
    'item_code' => env('BILLDESK_ITEM_CODE', 'DIRECT'),

    // Child Window
    'child_window' => env('BILLDESK_CHILD_WINDOW', 'false'),

    // Retry Count
    'retry_count' => env('BILLDESK_RETRY_COUNT', 3),

    // Debit Day
    'debit_day' => env('BILLDESK_DEBIT_DAY', '6'),

    // Init Channel
    'init_channel' => env('BILLDESK_INIT_CHANNEL', 'internet'),

    /*
     * The Default Currency
     *
     * set the default currency code used for transaction. You can reach out to BILLDESK to
     * find out what other currency are supported
     */
    'currency' => env('BILLDESK_CURRENCY', '356'),

    /*
     * Urls List
     *
     * the list of urls for uat and production
     *
     * each url is used for a specific request, please refer to documentation to learn more about when to use
     * each url.
     */
    'urls' => [
        'uat' => [
            'js_sdk'              => 'https://uat.billdesk.com/jssdk/v1/dist/',
            'create_order'        => 'https://pguat.billdesk.io/payments/ve1_2/orders/create',
            'create_mandate'      => 'https://pguat.billdesk.io/pgsi/ve1_2/mandatetokens/create',
            'update_mandate'      => 'https://pguat.billdesk.io/pgsi/v1_2/mandatetokens/update/create',
            'get_mandate'         => 'https://pguat.billdesk.io/pgsi/v1_2/mandatetokens/get',
            'list_mandate'        => 'https://pguat.billdesk.io/pgsi/v1_2/mandatetokens/list',
            'create_invoice'      => 'https://pguat.billdesk.io/pgsi/ve1_2/invoices/create',
            'get_invoice'         => 'https://pguat.billdesk.io/pgsi/ve1_2/invoices/get',
            'create_transaction'  => 'https://pguat.billdesk.io/payments/ve1_2/transactions/create',
            'get_transaction'     => 'https://pguat.billdesk.io/payments/ve1_2/transactions/get',
            'create_refund'       => 'https://pguat.billdesk.io/payments/ve1_2/refunds/create',
            'get_refund'          => 'https://pguat.billdesk.io/payments/ve1_2/refunds/get',
        ],
        'production' => [
            'js_sdk'              => 'https://pay.billdesk.com/jssdk/v1/dist/',
            'create_order'        => 'https://api.billdesk.com/payments/ve1_2/orders/create',
            'create_mandate'      => 'https://api.billdesk.com/pgsi/ve1_2/mandatetokens/create',
            'update_mandate'      => 'https://api.billdesk.com/pgsi/v1_2/mandatetokens/update/create',
            'get_mandate'         => 'https://api.billdesk.com/pgsi/v1_2/mandatetokens/get',
            'list_mandate'        => 'https://api.billdesk.com/pgsi/v1_2/mandatetokens/list',
            'create_invoice'      => 'https://api.billdesk.com/pgsi/ve1_2/invoices/create',
            'get_invoice'         => 'https://api.billdesk.com/pgsi/ve1_2/invoices/get',
            'create_transaction'  => 'https://api.billdesk.com/payments/ve1_2/transactions/create',
            'get_transaction'     => 'https://api.billdesk.com/payments/ve1_2/transactions/get',
            'create_refund'       => 'https://api.billdesk.com/payments/ve1_2/refunds/create',
            'get_refund'          => 'https://api.billdesk.com/payments/ve1_2/refunds/get',
        ],
    ],
];
