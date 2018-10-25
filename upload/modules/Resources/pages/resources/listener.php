<?php
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr5
 *
 *  License: MIT
 *
 *  Resources - listener
 */

$data = $queries->getWhere('settings', array('name', '=', 'resources_paypal_hook'));

if(!count($data))
	die();
else
	$data = $data[0]->value;

if(isset($_GET['key']) && $_GET['key'] == $data){
	// Success
	$webhookId = $queries->getWhere('settings', array('name', '=', 'resources_paypal_hook_id'));
	if(!count($webhookId))
		die();
	else
		$webhookId = $webhookId[0]->value;

	require_once(ROOT_PATH . '/modules/Resources/paypal.php');

	$bodyReceived = file_get_contents('php://input');

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
		$transaction = $queries->getWhere('resources_payments', array('transaction_id', '=', $transaction));

		if(!count($transaction)){
			ErrorHandler::logCustomError('[PayPal] Could not find transaction ' . Output::getClean($transaction) . ' in the system.');
			die();
		} else
			$transaction = $transaction[0]->id;

		switch($response->event_type){
			case 'PAYMENT.SALE.COMPLETED':
				// Grant access to a resource
				$queries->update('resources_payments', $transaction, array(
					'status' => 1
				));

				// TODO: alerts
				// Alert::create();

				break;

			case 'PAYMENT.SALE.DENIED':
			case 'PAYMENT.SALE.REFUNDED':
			case 'PAYMENT.SALE.REVERSED':
				// Revoke access to a resource
				$queries->update('resources_payments', $transaction, array(
					'status' => 2
				));

				// TODO: alerts
				// Alert::create();

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