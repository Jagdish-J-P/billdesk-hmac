<!DOCTYPE html>
<html lang="en">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<head>
    <meta charset="utf-8">
    <title>{{ config('app.name', 'Laravel Billdesk Implementation') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous" />
    <link href="{{ asset('assets/vendor/billdesk-hmac/css/style.css') }}" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="./">
                <img src="{{ url(config('billdesk.merchant_logo')) }}" alt="" width="180px" />
            </a>
        </div>
    </nav>
    <section class="paymentform">
        <div class="container">
            <div class="mainform">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <li>{{ implode('<li>', $errors->all()) }}
                    </div>
                @endif
                <div>
                    <div class="payment-type">
                        <label>Nature of Payment <span class="requiredfild">*</span> </label>
                        <div class="form-check form-check-inline">
                            <input type="radio" class="form-check-input" id="validationFormCheck2" name="nature_of_payment"
                                value="otp" required checked />
                            <label class="form-check-label" for="validationFormCheck2">One Time Payment</label>
                        </div>
                        <div class="form-check form-check-inline mb-3">
                            <input type="radio" class="form-check-input" id="validationFormCheck3" name="nature_of_payment"
                                value="si" required />
                            <label class="form-check-label" for="validationFormCheck3">Standing Instructions
                                (SI)</label>
                            <div class="invalid-feedback">More example invalid feedback text</div>
                        </div>
                    </div>
                    <div class="row">
                        <form class="form-control" method="POST" action="{{ route('billdesk.payment.auth.request') }}">
                            @csrf
                            <div class="row">

                                <div class="col-lg-6 mb-4">
                                    <label for="first_name" class="form-label">First Name <span
                                            class="requiredfild">*</span></label>
                                    <input type="text" class="form-control" id="first_name"
                                        name="customer[first_name]" placeholder="First Name" required>
                                    <input type="hidden" id="mandate_required" name="mandate_required" value="N" />
                                    <input type="hidden" id="customerId" name="mandate[customer_refid]" value="{{ uniqid() }}" />
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <label for="last_name" class="form-label">Last Name <span
                                            class="requiredfild">*</span></label>
                                    <input type="text" class="form-control" id="last_name"
                                        name="customer[last_name]" placeholder="Last Name" required>
                                </div>

                                <div class="col-lg-6 mb-4">
                                    <label for="email" class="form-label">Email <span
                                            class="requiredfild">*</span></label>
                                    <input type="text" class="form-control" id="email" placeholder="Email"
                                        name="customer[email]" required>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <label for="mobile_number" class="form-label">Mobile Number <span
                                            class="requiredfild">*</span></label>
                                    <input type="number" class="form-control" id="mobile_number"
                                        name="customer[mobile]" placeholder="Mobile Number" required>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <label for="city" class="form-label">City <span
                                            class="requiredfild">*</span></label>
                                    <input type="text" class="form-control" id="city" placeholder="City"
                                        name="additional_info[additional_info1]" required>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <label for="sales_executive" class="form-label">Sales Executive <span
                                            class="requiredfild">*</span></label>
                                    <input type="text" class="form-control" id="sales_executive"
                                        name="additional_info[additional_info2]" placeholder="Sales Executive" required>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <label for="member_id" class="form-label">Member ID</label>
                                    <input type="text" class="form-control" id="member_id"
                                        name="additional_info[additional_info3]" placeholder="Member ID">
                                </div>
                            </div>
                            <div class="box otp" style="display: block;">
                                <div class="row">

                                    <div class="subscr">
                                        <h3 class="payheader">Amount Summary</h3>
                                    </div>
                                    <div class="col-lg-6 mb-4">
                                        <label for="np" class="form-label">Nature of Payment</label>
                                        <select id="np" class="form-select"
                                            name="additional_info[additional_info4]"
                                            aria-label="Default select example">
                                            <option selected>Nature of Payment</option>
                                            <option value="1">DP</option>
                                            <option value="2">EMI</option>
                                            <option value="3">FIT</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-6 pb-1">
                                        <label for="amount" class="form-label">Amount <span
                                                class="requiredfild">*</span></label>
                                        <input type="number" class="form-control" id="amount" name="amount"
                                            placeholder="Amount" >
                                    </div>

                                    <div class="col-lg-12 pb-1">
                                        <label for="remarks">Remarks (Optional)</label>
                                        <textarea class="form-control" placeholder="Remarks (Optional)" id="remarks"
                                            name="additional_info[additional_info5]" style="height: 100px"></textarea>
                                    </div>

                                    <div class="text-center mt-3 mb-3">
                                        <button class="btn btn-primary" type="submit">Pay Now</button>
                                    </div>

                                </div>
                            </div>
                            <div class="si box">

                                <div class="row">
                                    <div class="subscr">
                                        <h3 class="payheader">Subscription Summary</h3>
                                    </div>
                                    <div class="col-lg-3 pb-1">
                                        <label for="frequency" class="form-label">Frequency</label>
                                        <input type="text" class="form-control" id="frequency" name="mandate[frequency]"
                                            placeholder="Frequency" value="monthly" required readonly>
                                    </div>
                                    <div class="col-lg-3 pb-1">
                                        <label for="duration" class="form-label">Duration <span
                                                class="requiredfild">*</span></label>
                                        <input type="number" class="form-control" id="duration"
                                            placeholder="Duration" value="6" >
                                    </div>
                                    <div class="col-lg-3 pb-1">
                                        <label for="start_date" class="form-label">Start Date <span
                                                class="requiredfild">*</span></label>
                                        <input type="date" class="form-control" id="start_date" name="mandate[start_date]"
                                            placeholder="Start Date" >
                                    </div>
                                    <div class="col-lg-3 pb-1">
                                        <label for="end_date" class="form-label">End Date <span
                                                class="requiredfild">*</span></label>
                                        <input type="date" class="form-control" id="end_date" name="mandate[end_date]"
                                            placeholder="End Date" >
                                        <input type="hidden" name="mandate[amount_type]" value="max" />
                                        <input type="hidden" name="mandate[recurrence_rule]" value="on" />
                                        <input type="hidden" name="mandate[debit_day]" value="6" />
                                    </div>
                                    <div class="subscr">
                                        <h3 class="payheader">Amount Summary</h3>
                                    </div>
                                    <div class="col-lg-4 pb-1">
                                        <label for="frequency" class="form-label">Down Payment Amount</label>
                                        <div class="input-group">
                                            <span class="input-group-text" id="basic-addon1">INR</span>
                                            <input type="text" class="form-control" id="amount" name="amount"
                                                placeholder="Frequency" value="1.00"  readonly>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 pb-1">
                                        <label for="duration" class="form-label">Subscription Amount <span
                                                class="requiredfild">*</span></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="amount" name="mandate[amount]"
                                                placeholder="Subscription Amount" >
                                        </div>
                                    </div>

                                    <div class="col-lg-12 pb-1">
                                        <label for="remarks">Remarks (Optional)</label>
                                        <textarea class="form-control" placeholder="Remarks (Optional)"  id="remark" name="mandate[subscription_desc]" style="height: 100px"></textarea>
                                    </div>

                                    <div class="text-center mt-3 mb-3">
                                        <button class="btn btn-primary" type="submit">Pay Now</button>
                                    </div>

                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://code.jquery.com/jquery-3.6.3.min.js"
        integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=" crossorigin="anonymous"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            $('input[type="radio"]').click(function() {
                var inputValue = $(this).attr("value");
                var targetBox = $("." + inputValue);
                $(".box").not(targetBox).hide();
                $(targetBox).show();
                $("#mandate_required").val(inputValue == 'si' ? 'Y' : 'N');
            });
        });

        window.oncontextmenu = function() {
            return false;
        }

        $(document).keydown(function(event) {
            if (event.keyCode == 123) {
                return false;
            } else if ((event.ctrlKey && event.shiftKey && event.keyCode == 73) || (event.ctrlKey && event
                    .shiftKey && event.keyCode == 74) || (event.ctrlKey && event.keyCode == 85)) {
                return false;
            }
        });
    </script>

</body>

</html>
