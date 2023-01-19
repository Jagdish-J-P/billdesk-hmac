<?php

namespace JagdishJP\BilldeskHmac;

use JagdishJP\BilldeskHmac\Messages\CreateOrder;
use JagdishJP\BilldeskHmac\Messages\TransactionEnquiry;

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
        $transactionEnquiry = (new TransactionEnquiry())->handle(compact('reference_id'));

        $dataList = $transactionEnquiry->getData();

        $response = $transactionEnquiry->connect($dataList);

        $responseData = $transactionEnquiry->parseResponse($response);

        if ($responseData === false) {
            return [
                'status'         => 'failed',
                'message'        => 'We could not find any data',
                'transaction_id' => null,
                'reference_id'   => $reference_id,
            ];
        }

        return $responseData;
    }
}
