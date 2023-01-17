<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel Billdesk Implementation') }}</title>
    {{-- @billdesksdk() --}}
    <script type='module' src='https://uat.billdesk.com/jssdk/v1/dist/billdesksdk/billdesksdk.esm.js'></script>
    <script nomodule='' src='https://uat.billdesk.com/jssdk/v1/dist/billdesksdk.js'></script>
    <link href='https://uat.billdesk.com/jssdk/v1/dist/billdesksdk/billdesksdk.css' rel='stylesheet'>
</head>

<body>
    Please wait iniating payment.
    
    <script>
        var flow_config = {
            merchantId: "{{ config('billdesk.merchant_id') }}",
            bdOrderId: "{{ $request['bdOrderId'] }}",
            authToken: "{{ $request['authToken'] }}",
            childWindow: true,
            returnUrl: "{{ $request['response_url'] }}",
            retryCount: 3
        }

        var responseHandler = function(txn) {
            console.log("callback received status:: ", txn.status)
            console.log("callback received response:: ", txn.response)
        }

        var config = {
            responseHandler: responseHandler,
            merchantLogo: "{{ $request['merchant_logo'] }}",
            flowConfig: flow_config,
            flowType: "payments"
        }

        window.loadBillDeskSdk(config);
    </script>

</body>

</html>
