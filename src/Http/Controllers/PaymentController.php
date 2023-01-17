<?php

namespace JagdishJP\BilldeskHmac\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use JagdishJP\BilldeskHmac\Messages\CreateOrder;

class PaymentController extends Controller
{
    /**
     * Initiate the request authorization message to FPX.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request)
    {
        return view('billdesk-hmac::redirect_to_bank', [
            'request' => (new CreateOrder())->handle($request->all()),
        ]);
    }
}
