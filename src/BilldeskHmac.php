<?php

namespace JagdishJP\BilldeskHmac;

use JagdishJP\BilldeskHmac\Messages\CreateOrder;
use JagdishJP\BilldeskHmac\Messages\InvoiceCreate;
use JagdishJP\BilldeskHmac\Messages\InvoiceGet;
use JagdishJP\BilldeskHmac\Messages\MandateDelete;
use JagdishJP\BilldeskHmac\Messages\MandateList;
use JagdishJP\BilldeskHmac\Messages\MandateModify;
use JagdishJP\BilldeskHmac\Messages\MandateTokenCreate;
use JagdishJP\BilldeskHmac\Messages\RefundEnquiry;
use JagdishJP\BilldeskHmac\Messages\RefundOrder;
use JagdishJP\BilldeskHmac\Messages\TransactionCreate;
use JagdishJP\BilldeskHmac\Messages\TransactionStatus;

class BilldeskHmac
{
    /**
     * Creates Order.
     *
     * @param array $payload 
     *
     * @return array
     */
    public static function createOrder(array $payload)
    {
        return (new CreateOrder())->handle($payload);
    }
    
    /**
     * Returns status of transaction.
     *
     * @param string $reference_id reference order id
     *
     * @return array
     */
    public static function getTransactionStatus(string $reference_id)
    {
        return (new TransactionStatus())->handle(compact('reference_id'));
    }

    /**
     * Refund Order.
     *
     * @param array $payload 
     *
     * @return array
     */
    public static function refundOrder(array $payload)
    {
        return (new RefundOrder())->handle($payload);
    }

    /**
     * Refund Order Status.
     *
     * @param array $payload 
     *
     * @return array
     */
    public static function refundOrderStatus(array $payload)
    {
        return (new RefundEnquiry())->handle($payload);
    }

    /**
     * Returns list of mandates.
     *
     * @param array $parameters 
     *
     * @return array
     */
    public static function mandateList(array $parameters)
    {
        return (new MandateList())->handle($parameters);
    }

    /**
     * Returns list of mandates.
     *
     * @param array $parameters 
     *
     * @return array
     */
    public static function mandateModify(array $parameters)
    {
        $response = (new MandateModify())->handle($parameters);

        $response['flowType'] = 'modify_mandate';

        return $response;
    }

    /**
     * Returns list of mandates.
     *
     * @param array $parameters 
     *
     * @return array
     */
    public static function mandateDelete(array $parameters)
    {
        $response = (new MandateDelete())->handle($parameters);

        $response['flowType'] = 'modify_mandate';

        return $response;
    }

    /**
     * Creates Invoice.
     *
     * @param array $payload 
     *
     * @return array
     */
    public static function invoiceCreate(array $payload)
    {
        return (new InvoiceCreate())->handle($payload);
    }

    /**
     * Get Invoice.
     *
     * @param array $payload 
     *
     * @return array
     */
    public static function invoiceGet(string $invoice_number)
    {
        return (new InvoiceGet())->handle(compact('invoice_number'));
    }

    
    /**
     * Creates Transaction for Invoice.
     *
     * @param array $payload 
     *
     * @return array
     */
    public static function transactionCreate(array $payload)
    {
        return (new TransactionCreate())->handle($payload);
    }

    /**
     * Refund Order Status.
     *
     * @param array $payload 
     *
     * @return array
     */
    public static function decrypt(string $response)
    {
        return (new RefundEnquiry())->verifyAndDecrypt($response);
    }
}
