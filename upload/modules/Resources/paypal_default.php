<?php
require_once(ROOT_PATH . '/modules/Resources/PayPal-PHP-SDK/autoload.php');

$apiContext = new \PayPal\Rest\ApiContext(
    new \PayPal\Auth\OAuthTokenCredential(
        '{client_id}',
        '{client_secret}'
    )
);

$apiContext->setConfig(
    [
        'log.LogEnabled' => true,
        'log.FileName' => ROOT_PATH . '/cache/logs/PayPal.log',
        'log.LogLevel' => 'FINE',
        'mode' => 'live',
    ]
);

try {
    $data = DB::getInstance()->get('settings', ['name', '=', 'resources_paypal_hook']);
    if (!$data->count()) {
        $key = md5(uniqid());

        // Create API webhook
        $webhook = new \PayPal\Api\Webhook();
        $webhookEventTypes = [];

        $webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"PAYMENT.SALE.COMPLETED"}');
        $webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"PAYMENT.SALE.DENIED"}');
        $webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"PAYMENT.SALE.REFUNDED"}');
        $webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"PAYMENT.SALE.REVERSED"}');

        $webhook->setUrl(rtrim(Util::getSelfURL(), '/') . URL::build('/resources/listener/', 'key=' . $key));
        $webhook->setEventTypes($webhookEventTypes);
        $output = $webhook->create($apiContext);
        $id = $output->getId();

        DB::getInstance()->insert('settings', [
            'name' => 'resources_paypal_hook_id',
            'value' => $id
        ]);

        DB::getInstance()->insert('settings', [
            'name' => 'resources_paypal_hook',
            'value' => $key
        ]);
    }
} catch(Exception $e){
    ErrorHandler::logCustomError($e->getData());
}
