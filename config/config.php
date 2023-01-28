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

    /**
     * The UAT Prefix to identify test transactions
     * 
     */ 
    'uat_prefix' => env('BILLDESK_UAT_PREFIX', ''),

    /**
     * The UAT Prefix to identify test transactions
     * 
     */ 
    'merchant_logo' => env('BILLDESK_MERCHANT_LOGO'),

    /*
     * Response URL used by BillDesk to direct the user back to your platform after a transaction is completed
     *
     * Example: https://localhost.test/billdesk/payment/callback
     */
    'response_url' => env('BILLDESK_RESPONSE_URL'),

    /*
     * The response url path without the domain and scheme
     *
     * Example: billdesk/payment/callback
     */
    'response_path' => env('BILLDESK_RESPONSE_PATH'),

    /*
     * host-to-host url used by BILLDESK to send direct messages to your app without the need for users actions
     *
     * Example: https://localhost.test/BillDesk/payment/webhook
     */
    'webhook_url' => env('BILLDESK_WEBHOOK_URL'),

    /*
     * The indirect url path without the domain and scheme
     *
     * Example: BillDesk/payment/webhook
     */
    'webhook_path' => env('BILLDESK_WEBHOOK_PATH'),

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
    'debit_day' => env('BILLDESK_DEBIT_DAY', "6"),
    
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
            'create_order'        => 'https://pay.billdesk.io/payments/ve1_2/orders/create',
            'create_mandate'      => 'https://pay.billdesk.io/pgsi/ve1_2/mandatetokens/create',
            'update_mandate'      => 'https://pay.billdesk.io/pgsi/v1_2/mandatetokens/update/create',
            'get_mandate'         => 'https://pay.billdesk.io/pgsi/v1_2/mandatetokens/get',
            'list_mandate'        => 'https://pay.billdesk.io/pgsi/v1_2/mandatetokens/list',
            'create_invoice'      => 'https://pay.billdesk.io/pgsi/ve1_2/invoices/create',
            'get_invoice'         => 'https://pay.billdesk.io/pgsi/ve1_2/invoices/get',
            'create_transaction'  => 'https://pay.billdesk.io/payments/ve1_2/transactions/create',
            'get_transaction'     => 'https://pay.billdesk.io/payments/ve1_2/transactions/get',
            'create_refund'       => 'https://pay.billdesk.io/payments/ve1_2/refunds/create',
            'get_refund'          => 'https://pay.billdesk.io/payments/ve1_2/refunds/get',
        ],
    ],
];
