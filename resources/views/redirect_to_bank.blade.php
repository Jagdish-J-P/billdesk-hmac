<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel Billdesk Implementation') }}</title>
    
    @billdesksdk()
    
</head>

<body>
    Please wait iniating payment.
    
    <script>
        var flow_config = {
            merchantId: "{{ config('billdesk.merchant_id') }}",
            bdOrderId: "{{ $request['bdOrderId'] }}",
            authToken: "{{ $request['authToken'] }}",
            childWindow: {{ config('billdesk.child_window') }},
            returnUrl: "{{ (!config('billdesk.child_window')) ? $request['response_url'] : '<html><head><title>Billdesk</title></head><body onload=\"window.close();\"></body></html>' }}",
            retryCount: {{ config('billdesk.retry_count') }}
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
