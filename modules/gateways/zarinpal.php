<?php
/*
 - Author : GoldenSource.iR 
 - Module Designed For The : zarinpal.com
 - Mail : Mail@GoldenSource.ir
*/

use WHMCS\Database\Capsule;
if(isset($_REQUEST['invoiceId']) && is_numeric($_REQUEST['invoiceId'])){
    require_once __DIR__ . '/../../init.php';
    require_once __DIR__ . '/../../includes/gatewayfunctions.php';
    require_once __DIR__ . '/../../includes/invoicefunctions.php';
    $gatewayParams = getGatewayVariables('zarinpal');
    if($gatewayParams['testMode'] == 'on'){
        $zarinClient = new SoapClient('https://sandbox.zarinpal.com/pg/services/WebGate/wsdl', ['encoding' => 'UTF-8']);
    } else {
        if($gatewayParams['mirror'] == 'IR'){
            $zarinClient = new SoapClient('https://ir.zarinpal.com/pg/services/WebGate/wsdl', ['encoding' => 'UTF-8']);
        } else {
            $zarinClient = new SoapClient('https://de.zarinpal.com/pg/services/WebGate/wsdl', ['encoding' => 'UTF-8']);
        }
    }
    if(isset($_REQUEST['Authority'], $_GET['Status'], $_GET['Authority'], $_REQUEST['callback']) && $_REQUEST['callback'] == 1){
        $invoice = Capsule::table('tblinvoices')->where('id', $_REQUEST['invoiceId'])->where('status', 'Unpaid')->first();
        if(!$invoice){
            die("Invoice not found");
        }
        if ($_GET['Status'] == 'OK') {
            $amount = ceil($invoice->total / ($gatewayParams['currencyType'] == 'IRT' ? 1 : 10));
            if($gatewayParams['feeFromClient'] == 'on'){
                $amount = ceil(1.01 * $amount);
            }
            $result = $zarinClient->PaymentVerification([
                'MerchantID' => $gatewayParams['MerchantID'],
                'Authority' => $_GET['Authority'],
                'Amount' => $amount,
            ]);
            if ($result->Status == 100) {
                checkCbTransID($result->RefID);
                logTransaction($gatewayParams['name'], $_REQUEST, 'Success');
                addInvoicePayment(
                    $invoice->id,
                    $result->RefID,
                    $invoice->total,
                    0,
                    'zarinpal'
                );
            } else {
                logTransaction($gatewayParams['name'], array(
                    'Code'        => 'Zarinpal Status Code',
                    'Message'     => $result->Status,
                    'Transaction' => $_GET['Authority'],
                    'Invoice'     => $invoice->id,
                    'Amount'      => $invoice->total,
                ), 'Failure');
            }
        }
        header('Location: ' . $gatewayParams['systemurl'] . '/viewinvoice.php?id=' . $invoice->id);
    } else if(isset($_SESSION['uid'])){
        $invoice = Capsule::table('tblinvoices')->where('id', $_REQUEST['invoiceId'])->where('status', 'Unpaid')->where('userid', $_SESSION['uid'])->first();
        if(!$invoice){
            die("Invoice not found");
        }
        $client = Capsule::table('tblclients')->where('id', $_SESSION['uid'])->first();
        $amount = ceil($invoice->total / ($gatewayParams['currencyType'] == 'IRT' ? 1 : 10));
        if($gatewayParams['feeFromClient'] == 'on'){
            $amount = ceil(1.01 * $amount);
        }
        $result = $zarinClient->PaymentRequest([
            'MerchantID' => $gatewayParams['MerchantID'],
            'Amount' => $amount,
            'Description' => sprintf('پرداخت فاکتور #%s', $invoice->id),
            'Email' => null,
            'Mobile' => null,
            'CallbackURL' => $gatewayParams['systemurl'] . '/modules/gateways/zarinpal.php?invoiceId=' . $invoice->id . '&callback=1',
        ]);
        if ($result->Status == 100) {
            if($gatewayParams['testMode'] == 'on'){
                if($gatewayParams['zarinGate'] == 'on'){
                    header('Location: https://sandbox.zarinpal.com/pg/StartPay/'.$result->Authority.'/ZarinGate');
                } else {
                    header('Location: https://sandbox.zarinpal.com/pg/StartPay/'.$result->Authority);
                }
            } else {
                if($gatewayParams['zarinGate'] == 'on'){
                    header('Location: https://www.zarinpal.com/pg/StartPay/'.$result->Authority.'/ZarinGate');
                } else {
                    header('Location: https://www.zarinpal.com/pg/StartPay/'.$result->Authority);
                }
            }
        } else {
            echo 'اتصال به درگاه امکان پذیر نیست: ', $result->Status;
        }
    }
    return;
}

if (!defined('WHMCS')) {
	die('This file cannot be accessed directly');
}

function zarinpal_MetaData()
{
    return array(
        'DisplayName' => 'ماژول پرداخت آنلاین ZarinPal.com برای WHMCS',
        'APIVersion' => '1.0',
    );
}

function zarinpal_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'ZarinPal.com',
        ),
        'mirror' => array(
            'FriendlyName' => 'سرور',
            'Type' => 'dropdown',
            'Options' => array(
                'DE' => 'آلمان',
                'IR' => 'ایران',
            ),
        ),
        'currencyType' => array(
            'FriendlyName' => 'نوع ارز',
            'Type' => 'dropdown',
            'Options' => array(
                'IRR' => 'ریال',
                'IRT' => 'تومان',
            ),
        ),
        'MerchantID' => array(
            'FriendlyName' => 'مریجنت کد',
            'Type' => 'text',
            'Size' => '255',
            'Default' => '',
            'Description' => 'کد api دریافتی از سایت ZarinPal.com',
        ),
        'zarinGate' => array(
            'FriendlyName' => 'زرین گیت',
            'Type' => 'yesno',
            'Description' => 'در صورت استفاده از زرین گیت تیک بزنید',
        ),
        'feeFromClient' => array(
            'FriendlyName' => 'دریافت مالیات از کاربر',
            'Type' => 'yesno',
            'Description' => 'برای دریافت مالیات از کاربر تیک بزنید',
        ),
        'testMode' => array(
            'FriendlyName' => 'حالت تستی',
            'Type' => 'yesno',
            'Description' => 'برای فعال کردن حالت تستی تیک بزنید',
        ),
    );
}

function zarinpal_link($params)
{
    $htmlOutput = '<form method="GET" action="modules/gateways/zarinpal.php">';
    $htmlOutput .= '<input type="hidden" name="invoiceId" value="' . $params['invoiceid'] .'">';
    $htmlOutput .= '<input type="submit" value="' . $params['langpaynow'] . '" />';
    $htmlOutput .= '</form>';
    return $htmlOutput;
}
