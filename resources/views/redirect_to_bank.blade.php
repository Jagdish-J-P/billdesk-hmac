<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel Billdesk Implementation') }}</title>

    @billdesksdk()

</head>

<body onload="launchSdk()">
    Please wait iniating payment.

    <script>
        function launchSdk() {

            var flow_config = {
                merchantId: "{{ config('billdesk.merchant_id') }}",
                authToken: "{{ $request['authToken'] }}",
                @if (isset($request['bdOrderId']))
                    bdOrderId: "{{ $request['bdOrderId'] }}",
                @endif
                @if (isset($request['mandateTokenId']))
                    mandateTokenId: "{{ $request['mandateTokenId'] }}",
                @endif
                childWindow: {{ config('billdesk.child_window') ? 'true' : 'false' }},
                //returnUrl: "{{ !config('billdesk.child_window') ? $request['response_url'] : '<html><head><title>Billdesk</title></head><body onload=\"window.close();\"></body></html>' }}",
                retryCount: {{ config('billdesk.retry_count') }}
            }

            var responseHandler = function(txn) {
                if(txn.status != 200) {
                    window.location = "{{ route('billdesk.payment.failed') }}";
                }
            }

            var config = {
                responseHandler: responseHandler,
                merchantLogo: "{{ $request['merchant_logo'] }}",
                flowConfig: flow_config,
                flowType: "{{ $request['flowType'] }}"
            }

            window.loadBillDeskSdk(config);

        }
    </script>

</body>

</html>
