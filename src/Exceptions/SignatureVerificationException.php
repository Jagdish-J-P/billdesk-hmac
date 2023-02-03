<?php

namespace JagdishJP\BilldeskHmac\Exceptions;

use Exception;

class SignatureVerificationException extends Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
