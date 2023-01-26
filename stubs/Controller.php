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
    public function status(Request $request)
    {
        try{
            $response = BilldeskHmac::getTransactionStatus($request->reference_id);

            return redirect()->back();
        }
        catch(Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }
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
