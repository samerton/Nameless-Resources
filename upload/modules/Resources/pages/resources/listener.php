<?php
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr13
 *
 *  License: MIT
 *
 *  Resources - listener
 */

require(ROOT_PATH . '/modules/Resources/classes/Resources.php');

$data = DB::getInstance()->get('settings', array('name', '=', 'resources_paypal_hook'));

if (!$data->count())
	die();
else
	$data = $data->first()->value;

if(isset($_GET['key']) && $_GET['key'] == $data){
	// Success
	$webhookId = DB::getInstance()->get('settings', array('name', '=', 'resources_paypal_hook_id'));
	if (!$webhookId->count())
		die();
	else
		$webhookId = $webhookId->first()->value;

	require_once(ROOT_PATH . '/modules/Resources/paypal.php');

	$bodyReceived = file_get_contents('php://input');

	if (!function_exists('getallheaders')) {
		function getallheaders() {
		    	$build = array();
		    	foreach($_SERVER as $name => $value) {
				if (strpos($name, 'HTTP_PAYPAL') !== false) {
					$build[str_replace('_','-', substr($name, 5))] = $value;
				}
            		}
            		return $build;
		}
	}
	$headers = getallheaders();
	$headers = array_change_key_case($headers, CASE_UPPER);

	$signatureVerification = new \PayPal\Api\VerifyWebhookSignature();
	$signatureVerification->setAuthAlgo($headers['PAYPAL-AUTH-ALGO']);
	$signatureVerification->setTransmissionId($headers['PAYPAL-TRANSMISSION-ID']);
	$signatureVerification->setCertUrl($headers['PAYPAL-CERT-URL']);
	$signatureVerification->setWebhookId($webhookId);
	$signatureVerification->setTransmissionSig($headers['PAYPAL-TRANSMISSION-SIG']);
	$signatureVerification->setTransmissionTime($headers['PAYPAL-TRANSMISSION-TIME']);
	$signatureVerification->setRequestBody($bodyReceived);

	try {
		$output = $signatureVerification->post($apiContext);

		$response = json_decode($bodyReceived);

		if(!isset($response->resource->parent_payment)){
			ErrorHandler::logCustomError('[PayPal] Webhook did not return payment ID');
			die();
		}

		$transaction = $response->resource->parent_payment;
		$transaction = DB::getInstance()->get('resources_payments', array('transaction_id', '=', $transaction));

		if (!$transaction->count()){
			ErrorHandler::logCustomError('[PayPal] Could not find transaction ' . Output::getClean($transaction) . ' in the system.');
			die();
		} else
			$transaction = $transaction->first();

		$customer = new User($transaction->user_id);
		$resource = DB::getInstance()->query('SELECT `name`, `creator_id` FROM nl2_resources WHERE id = ?', array($transaction->resource_id))->first();

		switch($response->event_type){
			case 'PAYMENT.SALE.COMPLETED':
				// Grant access to a resource
				DB::getInstance()->update('resources_payments', $transaction->id, array(
					'status' => 1
				));

				Alert::create($transaction->user_id, 'resource_purchased', array('path' => ROOT_PATH . '/modules/Resources/language', 'file' => 'resources', 'term' => 'resource_purchased'), array('path' => ROOT_PATH . '/modules/Resources/language', 'file' => 'resources', 'term' => 'resource_purchased_full', 'replace' => '{{resource}}', 'replace_with' => $resource->name), Resources::buildURL($transaction->resource_id, $resource->name));
				Alert::create($resource->creator_id, 'resource_purchase', array('path' => ROOT_PATH . '/modules/Resources/language', 'file' => 'resources', 'term' => 'resource_purchase'), array('path' => ROOT_PATH . '/modules/Resources/language', 'file' => 'resources', 'term' => 'resource_purchase_full', 'replace' => array('{{user}}', '{{resource}}'), 'replace_with' => array($customer->getDisplayName(), $resource->name)), $customer->getProfileURL());

				break;

			case 'PAYMENT.SALE.DENIED':
			case 'PAYMENT.SALE.REFUNDED':
			case 'PAYMENT.SALE.REVERSED':
				// Revoke access to a resource
				DB::getInstance()->update('resources_payments', $transaction->id, array(
					'status' => 2
				));

				Alert::create($resource->creator_id, 'resource_license_revoked', array('path' => ROOT_PATH . '/modules/Resources/language', 'file' => 'resources', 'term' => 'resource_license_revoked'), array('path' => ROOT_PATH . '/modules/Resources/language', 'file' => 'resources', 'term' => 'resource_license_revoked_full', 'replace' => array('{{resource}}', '{{user}}'), 'replace_with' => array($resource->name, $customer->getDisplayName())), $customer->getProfileURL());

				break;

			default:
				// Error
				ErrorHandler::logCustomError('[PayPal] Unknown event type ' . Output::getClean($response->event_type));
				break;
		}
	} catch(\PayPal\Exception\PayPalInvalidCredentialException $e){
		// Error verifying webhook
		ErrorHandler::logCustomError('[PayPal] ' . $e->errorMessage());
	} catch(Exception $e){
		ErrorHandler::logCustomError('[PayPal] ' . $e->getMessage());
	}

} else
	die();
