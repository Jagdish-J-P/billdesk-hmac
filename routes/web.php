<?php

use App\Http\Controllers\BilldeskHmac\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

$webhookPath         = Config::get('billdesk.webhook_path');
$responsePath        = Config::get('billdesk.response_path');
$mandateResponsePath = Config::get('billdesk.mandate_response_path');

Route::get('billdesk/initiate/payment/{initiated_from?}/{test?}', [Controller::class, 'initiatePayment'])->name('billdesk.initiate.payment');
Route::post('billdesk/payment/request', [Controller::class, 'beginTransaction'])->name('billdesk.payment.auth.request');
Route::post('billdesk/refund/order', [Controller::class, 'refundOrder'])->name('billdesk.refund.order');
Route::get('billdesk/payment/status', [Controller::class, 'status'])->name('billdesk.payment.status');
Route::get('billdesk/invoice/status', [Controller::class, 'invoiceStatus'])->name('billdesk.invoice.status');
Route::post('billdesk/mandates/modify', [Controller::class, 'mandateModify'])->name('billdesk.mandates.modify');
Route::delete('billdesk/mandates/delete', [Controller::class, 'mandateDelete'])->name('billdesk.mandates.delete');
Route::post("billdesk/mandate/invoice/create", [Controller::class, 'invoiceCreate'])->name('billdesk.mandate.invoice.create');
Route::post($webhookPath, [Controller::class, 'webhook'])->name('billdesk.payment.webhook');
Route::any("$responsePath/{id?}", [Controller::class, 'callback'])->name('billdesk.payment.response.url');
Route::any("$mandateResponsePath/{id?}", [Controller::class, 'mandateCallback'])->name('billdesk.mandate.response.url');
Route::get("billdesk/payment/failed", [Controller::class, 'failed'])->name('billdesk.payment.failed');
