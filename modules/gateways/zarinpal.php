<?php
/**
 *
 * @author Amirhossein Matini (matiniamirhossein@gmail.com)
 * @link goldensource.ir
 * @author Farhad Sakhaei (farhad0@gmail.com)
 * @link parsmizban.com
 * @package Zarinpal Payment Gateway
 * @license This file is licensed to Golden source. You are not allowed to reuse this code for your other applications
 */

use WHMCS\Database\Capsule;
use WHMCSZarinpal\Core\WebService;
use WHMCSZarinpal\Enum\StatusEnum;

if ( ! defined( 'WHMCS' ) ) {
	die( 'This file cannot be accessed directly' );
}

function zarinpal_MetaData() {
	return array(
		'DisplayName' => 'ماژول پرداخت آنلاین ZarinPal.com برای WHMCS',
		'APIVersion'  => '1.0.7',
	);
}

function zarinpal_config() {
	return array(
		'FriendlyName' => array(
			'Type'  => 'System',
			'Value' => 'ZarinPal.IR',
		),
		'currencyType' => array(
			'FriendlyName' => 'واحد ارز',
			'Type'         => 'dropdown',
			'Options'      => array(
				'IRR' => 'ریال',
				'IRT' => 'تومان',
			),
		),
		'MerchantID'   => array(
			'FriendlyName' => 'کد API',
			'Type'         => 'text',
			'Size'         => '255',
			'Default'      => '',
			'Description'  => 'کد api دریافتی از سایت ZarinPal.com',
		),
		'VPN'          => array(
			'FriendlyName' => 'محدود سازی آی پی',
			'Type'         => 'text',
			'Size'         => '255',
			'Default'      => 'لطفا برای ادامه فعالیت ، فیلترشکن خود را خاموش کنید .',
			'Description'  => 'لطفا برای بخش محدودسازی آی پی ، یک متن تعیین کنید',
		),
		'testMode'     => array(
			'FriendlyName' => 'حالت تستی',
			'Type'         => 'yesno',
			'Description'  => 'برای فعال کردن حالت تستی تیک بزنید',
		),
	);
}

/**
 * @throws Exception
 */
function zarinpal_link( $params ) {
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

	require_once dirname( __DIR__ ) . '/addons/ZarinpalAddon/include/bootstrap.php';
	ZarinpalAddon_activate();
	$gatewayParams  = getGatewayVariables( 'zarinpal' );
	$iranAccessOnly = Capsule::table( 'mod_zarinpal_settings' )->where( 'name', 'iran_access_only' )->first()->value;
	if ( $iranAccessOnly == 1 && ! ZarinpalAddon_check_iran() ) {
		echo $gatewayParams['VPN'];
		die();
	}
	$amount        = ceil( $params['amount'] * ( $gatewayParams['currencyType'] == 'IRT' ? 10 : 1 ) );
	$uuid          = ZarinpalAddon_gen_uuid();
	$transactionId = Capsule::table( 'mod_zarinpal_transactions' )->insertGetId( [
		'uuid'       => $uuid,
		'user_id'    => $params['clientdetails']['userid'],
		'invoice_id' => $params['invoiceid'],
		'ip_address' => WebService::ipAddress(),
		'amount'     => $amount,
		'created_at' => time(),
		'updated_at' => time(),
	] );

	$data = [
		'merchant_id'  => $gatewayParams['MerchantID'],
		'amount'       => $amount,
		'description'  => sprintf( 'پرداخت فاکتور #%s', $params['invoiceid'] ),
		'metadata'     => [ 'email' => $params['clientdetails']['email'] ],
		'callback_url' => $gatewayParams['systemurl'] . '/modules/gateways/callback/zarinpal.php?uuid=' . $uuid,
	];

	$mobile = ZarinpalAddon_getMobileNumber( $params['clientdetails']['userid'] );
	if ( ! empty( $mobile ) ) {
		$data['metadata']['mobile'] = $mobile;
	}

	try {
		$result = zarinpal_req( $zarinpal_urls['request_url'][ $gatewayParams['testMode'] == 'on' ? 'sandbox' : 'production' ], $data );
	} catch ( Exception $e ) {
		throw new Exception( $e );
	}

	if ( is_numeric( $result['data']['code'] ) && (int) $result['data']['code'] === 100 ) {
		Capsule::table( 'mod_zarinpal_transactions' )->where( 'id', $transactionId )->update( [
			'authority'  => $result['data']['authority'],
			'updated_at' => time(),
		] );

		$htmlOutput = '
		<form method="POST" action="' . $zarinpal_urls['redirect_url'][ $gatewayParams['testMode'] == 'on' ? 'sandbox' : 'production' ] . $result['data']['authority'] . '">
			<input type="submit" value="' . $params['langpaynow'] . '" />
			<div>مبلغ: ' . $amount . ' ریال</div>
		</form>';

	} else {
		Capsule::table( 'mod_zarinpal_transactions' )->where( 'id', $transactionId )->update( [
			'status'          => StatusEnum::FAILED,
			'failure_message' => $result['errors']['message'],
			'updated_at'      => time(),
		] );

		$htmlOutput = '<div>در حال حاضر اتصال به درگاه امکان پذیر نیست: ' . $result['errors']['message'] . '</div>';
	}

	return $htmlOutput;

}

/**
 * @throws Exception
 */
function zarinpal_req( $url, array $parameters = [] ) {
	$curl = curl_init();

	curl_setopt_array( $curl, [
		CURLOPT_URL            => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING       => '',
		CURLOPT_MAXREDIRS      => 10,
		CURLOPT_TIMEOUT        => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST  => 'POST',
		CURLOPT_POSTFIELDS     => json_encode( $parameters ),
		CURLOPT_HTTPHEADER     => [
			'accept: application/json',
			'content-type: application/json',
		],
	] );

	$response = curl_exec( $curl );

	if ( curl_errno( $curl ) != 0 ) {
		$error = curl_error( $curl );
		curl_close( $curl );
		throw new Exception( $error );
	}

	curl_close( $curl );

	return json_decode( $response, true );
}
