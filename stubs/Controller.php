<?php

namespace App\Http\Controllers\BilldeskHmac;

use App\Http\Controllers\Controller as BaseController;
use Exception;
use Illuminate\Http\Request;
use JagdishJP\BilldeskHmac\Facades\BilldeskHmac;
use JagdishJP\BilldeskHmac\Http\Requests\TransactionConfirmationRequest;

class Controller extends BaseController
{
    /**
     * Initiate the request authorization message to BillDesk.
     *
     * @param Request $request
     * @param mixed $initiated_from
     * @param mixed $test
     *
     * @return \Illuminate\Http\Response
     */
    public function initiatePayment(Request $request, $initiated_from = 'HTML', $test = '')
    {
        $response_format = $initiated_from == 'app' ? 'JSON' : 'HTML';

        return view('billdesk-hmac::payment', compact('test', 'response_format', 'request'));
    }

    /**
     * Initiate the request authorization message to BillDesk.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function beginTransaction(Request $request)
    {
        return view('billdesk-hmac::redirect_to_bank', [
            'request' => BilldeskHmac::createOrder($request->all()),
        ]);
    }

    /**
     * Initiate the refund request to BillDesk.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function refundOrder(Request $request)
    {
        $refund_data['merc_refund_ref_no']    = $request->merc_refund_ref_no;
        $refund_data['orderid']               = $request->orderid;
        $refund_data['transaction_id']        = $request->transaction_id;
        $refund_data['transaction_date']      = $request->transaction_date;
        $refund_data['refund_amount']         = $request->refund_amount;
        $refund_data['txn_amount']            = $request->txn_amount;

        $response = BilldeskHmac::refundOrder($refund_data);
    }

    /**
     * Initiate the request authorization message to BillDesk.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function status(Request $request)
    {
        $response = BilldeskHmac::getTransactionStatus($request->orderid);
    }

    /**
     * Initiate mandate delete request to BillDesk.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function mandateDelete(Request $request)
    {
        $response = BilldeskHmac::mandateDelete($request->all());

        return view('billdesk-hmac::redirect_to_bank', [
            'request' => $response,
        ]);
    }

    /**
     * Initiate payment transaction to BillDesk.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function invoiceCreate(Request $request)
    {
        $invoice_request = $invoice_data = $request->all();

        $response = BilldeskHmac::invoiceCreate($invoice_request);
    }

    /**
     * Initiate the request transaction status to BillDesk.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function invoiceStatus(Request $request)
    {
        $response = BilldeskHmac::invoiceGet($request->invoice_no);
    }

    /**
     * Initiate payment transaction for invoce to BillDesk.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function transactionCreate(Request $request)
    {
        $invoice_request = $request->all();

        $response = BilldeskHmac::transactionCreate($invoice_request);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function failed(Request $request)
    {
        $response['status']  = 'cancelled';
        $response['message'] = 'Transaction Cancelled!!!';
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function callback(TransactionConfirmationRequest $request)
    {
        $response = $request->handle();

        if ($response['response_format'] == 'JSON') {
            return response()->json(['response' => $response, 'billdesk_response' => $request->all()]);
        }

        // Update your order status
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    public function webhook(TransactionConfirmationRequest $request)
    {
        $response = $request->handle();

        // Update your order status

        return 'OK';
    }
}
