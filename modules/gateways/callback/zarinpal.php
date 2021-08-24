<?php
/*
 - Author : GoldenSource.iR - Amirhossein Matini 
 - Module Designed For The : zarinpal.com
 - Mail : Mail@GoldenSource.ir - matiniamirhossein@gmail.com
 - This file is licensed to Golden source. You are not allowed to reuse this code for your other applications
*/
use WHMCS\Database\Capsule;
use WHMCSZarinpal\Core\WebService;
use WHMCSZarinpal\Enum\StatusEnum;

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';
require_once dirname(dirname(__DIR__)) . "/addons/ZarinpalAddon/include/bootstrap.php";

ZarinpalAddon_activate();

$zarinpal_urls = [
    'request_url' => [
        'sandbox'    => 'https://sandbox.zarinpal.com/pg/v4/payment/request.json',
        'production' => 'https://api.zarinpal.com/pg/v4/payment/request.json',
    ],

    'verify_url' => [
        'sandbox'    => 'https://sandbox.zarinpal.com/pg/v4/payment/verify.json',
        'production' => 'https://api.zarinpal.com/pg/v4/payment/verify.json',
    ],

    'redirect_url' => [
        'sandbox'    => 'https://sandbox.zarinpal.com/pg/StartPay/',
        'production' => 'https://www.zarinpal.com/pg/StartPay/',
    ],
];

$gatewayParams = getGatewayVariables('zarinpal');

if (!isset($_REQUEST['uuid'])) {
    WebService::redirect($gatewayParams['systemurl']);
    exit();
}

if (!isset($_REQUEST['Authority'], $_GET['Status'])) {
    WebService::redirect($gatewayParams['systemurl']);
    exit();
}

$transaction = Capsule::table('mod_zarinpal_transactions')->where('uuid', $_REQUEST['uuid'])->where('status', StatusEnum::PENDING)->first();

if (!$transaction) {
    die("Transaction not found");
}

$invoice = Capsule::table('tblinvoices')->where('id', $transaction->invoice_id)->where('status', 'Unpaid')->first();

if (!$invoice) {
    die("Invoice not found");
}

$result = zarinpal_req($zarinpal_urls['verify_url'][$gatewayParams['testMode'] == 'on' ? 'sandbox' : 'production'], [
    'merchant_id' => $gatewayParams['MerchantID'],
    'authority'   => $_GET['Authority'],
    'amount'      => (int)$transaction->amount,
]);

if ($_GET['Status'] === 'OK') {
    if (is_numeric($result['data']['code']) && (int)$result['data']['code'] === 100) {
        checkCbTransID($result['data']['ref_id']);
        logTransaction($gatewayParams['name'], $_REQUEST, 'Success');
        addInvoicePayment(
            $invoice->id,
            $result['data']['ref_id'],
            $transaction->amount / ($gatewayParams['currencyType'] == 'IRT' ? 10 : 1),
            0,
            'ZarinpalAddon'
        );
        Capsule::table('mod_zarinpal_transactions')->where('id', $transaction->id)->update([
            'status'      => StatusEnum::SUCCESS,
            'card_number' => $result['data']['card_pan'],
            'updated_at'  => time(),
        ]);
    } else if (is_numeric($result['data']['code']) && (int)$result['data']['code'] === 101) {
        // do nothing caused its duplicate
    } else {
        Capsule::table('mod_zarinpal_transactions')->where('id', $transaction->id)->update([
            'status'          => StatusEnum::FAILED,
            'failure_message' => $result['errors']['message'],
            'updated_at'      => time(),
        ]);
        logTransaction($gatewayParams['name'], array(
            'Code'        => 'Zarinpal Status Code',
            'Message'     => 'Code: ' . $result['data']['message'] . ', Message: ' . $result['data']['message'],
            'Transaction' => $_GET['Authority'],
            'Invoice'     => $invoice->id,
            'Amount'      => $transaction->amount * ($gatewayParams['currencyType'] == 'IRT' ? 1 : 10),
        ), 'Failure');
    }
} else {
    Capsule::table('mod_zarinpal_transactions')->where('id', $transaction->id)->update([
        'status'     => StatusEnum::CANCELLED,
        'updated_at' => time(),
    ]);
}

WebService::redirect($gatewayParams['systemurl'] . '/viewinvoice.php?id=' . $invoice->id);
