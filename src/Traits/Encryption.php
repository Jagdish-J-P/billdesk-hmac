<?php

namespace JagdishJP\BilldeskHmac\Traits;

use JagdishJP\BilldeskHmac\Exceptions\SignatureVerificationException;

trait Encryption
{

    public function encryptAndSign($payload, $headers = array())
    {
        $jweHeaders = array_merge($headers, array(
            'alg' => 'HS256'
        ));

        $jws = $this->jwsBuilder
            ->create()
            ->withPayload($payload)
            ->addSignature($this->clientJwk, $jweHeaders)
            ->build();

        return $this->jwsSerializer->serialize($jws, 0);
    }

    public function verifyAndDecrypt($token)
    {
        $jws = $this->jwsSerializer->unserialize($token);

        if (!$this->jwsVerifier->verifyWithKey($jws, $this->clientJwk, 0)) {
            throw new SignatureVerificationException("Failed to verify signature");
        }

        return json_decode($jws->getPayload(), true);
    }
}
