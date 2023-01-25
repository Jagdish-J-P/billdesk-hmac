<?php

namespace JagdishJP\BilldeskHmac\Constant;

class Response
{
    private $responseStatus;
    private $response;
    private $bdTraceId;
    private $bdTimestamp;

    public const STATUS = [
        '0300' => 'Success',
        '0399' => 'Invalid Authentication At Bank',
        'NA'   => 'Invalid Input in the Request Message',
        '0002' => 'BillDesk is waiting for Response from Bank',
        '0001' => 'Error at BillDesk',
    ];

    function __construct($responseStatus, $response, $bdTraceId, $bdTimestamp) {
        $this->responseStatus = $responseStatus;
        $this->response = (object)$response;        
        $this->bdTraceId = $bdTraceId;
        $this->bdTimestamp = $bdTimestamp;
    }

    public function getResponseStatus() {
        return $this->responseStatus;
    }

    public function getResponse() {
        return $this->response;
    }

    public function getBdTraceid() {
        return $this->bdTraceId;
    }

    public function getBdTimestamp() {
        return $this->bdTimestamp;
    }
}
