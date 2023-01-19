<?php

use App\Http\Controllers\BilldeskHmac\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

$webhookPath  = Config::get('billdesk.webhook_path');
$responsePath = Config::get('billdesk.response_path');

Route::get('billdesk/initiate/payment/{initiated_from?}/{test?}', [Controller::class, 'initiatePayment'])->name('billdesk.initiate.payment');
Route::post('billdesk/payment/request', [Controller::class, 'handle'])->name('billdesk.payment.auth.request');
Route::post($webhookPath, [Controller::class, 'webhook'])->name('billdesk.payment.webhook');
Route::post($responsePath, [Controller::class, 'callback'])->name('billdesk.payment.response.url');
