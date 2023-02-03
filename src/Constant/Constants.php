<?php

namespace JagdishJP\BilldeskHmac\Constant;

use Monolog\Logger;

class Constants
{
    public const PG_PROD_BASE_URL = '';

    public const CREATE_ORDER_URL = 'payments/ve1_2/orders/create';

    public const CREATE_TRANSACTION_URL = '';

    public const REFUND_TRANSACTION_URL = '';

    public const HEADER_BD_TRACE_ID = 'BD-Traceid';

    public const HEADER_BD_TIMESTAMP = 'BD-Timestamp';

    public const JWE_HEADER_CLIENTID = 'clientid';

    public const LOG_CHANNEL = 'billdesk-client-php';

    public static $logger;

    public static function init()
    {
        self::$logger = new Logger(Constants::LOG_CHANNEL);
    }

    public static function createOrderURL($baseUrl = Constants::PG_PROD_BASE_URL)
    {
        return $baseUrl . '/' . Constants::CREATE_ORDER_URL;
    }

    public static function createTransactionURL($baseUrl = Constants::PG_PROD_BASE_URL)
    {
        return $baseUrl . '/' . Constants::CREATE_ORDER_URL;
    }

    public static function refundTransactionURL($baseUrl = Constants::PG_PROD_BASE_URL)
    {
        return $baseUrl . '/' . Constants::CREATE_ORDER_URL;
    }
}

Constants::init();
