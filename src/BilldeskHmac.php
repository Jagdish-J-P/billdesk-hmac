<?php

namespace JagdishJP\BilldeskHmac;

use App\Models\Order;
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
        return (new TransactionEnquiry())->handle(compact('reference_id'));

    }
}
