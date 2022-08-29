<?php
/*
 *  Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr13
 *
 *  License: MIT
 *
 *  Resources - purchase
 */
// Always define page name
define('PAGE', 'resources');
define('RESOURCE_PAGE', 'purchase');

if(!$user->isLoggedIn()){
    Redirect::to(URL::build('/resources'));
}

$groups = [];
foreach ($user->getGroups() as $group) {
    $groups[] = $group->id;
}

// Get resource
$rid = explode('/', $route);
$rid = $rid[count($rid) - 1];

if (!strlen($rid)) {
    Redirect::to(URL::build('/resources'));
}

$rid = explode('-', $rid);
if(!is_numeric($rid[0])){
    Redirect::to(URL::build('/resources'));
}
$rid = $rid[0];

$resource = DB::getInstance()->get('resources', ['id', '=', $rid]);
if (!$resource->count()) {
    Redirect::to(URL::build('/resources'));
}
$resource = $resource->first();

if($user->data()->id == $resource->creator_id || $resource->type == 0){
    // Can't purchase own resource
    Redirect::to(URL::build('/resources'));
}

require(ROOT_PATH . '/modules/Resources/classes/Resources.php');
$resources = new Resources();

// Check permissions
if (!$resources->canDownloadResourceFromCategory($groups, $resource->category_id)) {
    // Can't view
    Redirect::to(URL::build('/resources'));
}

// Already purchased?
$already_purchased = DB::getInstance()->query('SELECT id, status FROM nl2_resources_payments WHERE resource_id = ? AND user_id = ?', [$resource->id, $user->data()->id])->results();
if(count($already_purchased)){
    $already_purchased_id = $already_purchased[0]->id;
    $already_purchased = $already_purchased[0]->status;

    if($already_purchased == 0 || $already_purchased == 1){
        // Already purchased
        Redirect::to(URL::build('/resources/resource/' . Output::getClean($resource->id . '-' . Util::stringToURL($resource->name))));
    }
}

if(isset($_GET['do'])){
    require_once(ROOT_PATH . '/modules/Resources/paypal.php');

    if($_GET['do'] == 'complete'){
        // Insert into database
        if(!isset($_SESSION['resource_purchasing'])){
            // Error, resource ID has been lost
            Session::flash('purchase_resource_error', $resource_language->get('resources', 'sorry_please_try_again'));
            Redirect::to(URL::build('/resources/purchase/' . Output::getClean($resource->id . '-' . Util::stringToURL($resource->name))));

        } else {
            $paymentId = $_GET['paymentId'];
            $payment = \PayPal\Api\Payment::get($paymentId, $apiContext);

            $execution = new \PayPal\Api\PaymentExecution();
            $execution->setPayerId($_GET['PayerID']);

            try {
                $result = $payment->execute($execution, $apiContext);

                $payment = \PayPal\Api\Payment::get($paymentId, $apiContext);

            } catch(Exception $e){
                Session::flash('purchase_resource_error', $resource_language->get('resources', 'error_while_purchasing'));
                ErrorHandler::logCustomError($e->getMessage());
                Redirect::to(URL::build('/resources/purchase/' . Output::getClean($resource->id . '-' . Util::stringToURL($resource->name))));
            }

            if(isset($already_purchased_id) && $already_purchased == 2){
                // Update a cancelled purchase
                DB::getInstance()->update('resources_payments', $already_purchased_id, [
                    'status' => 0,
                    'created' => date('U'),
                    'transaction_id' => $payment->getId()
                ]);

            } else {
                // Create a new purchase
                DB::getInstance()->insert('resources_payments', [
                    'status' => 0,
                    'created' => date('U'),
                    'user_id' => $user->data()->id,
                    'resource_id' => $resource->id,
                    'transaction_id' => $payment->getId()
                ]);
            }

            // TODO: alerts
            //Alert::create('');
        }

    }

} else {
    if(Input::exists()){
        if(Token::check(Input::get('token'))){
            if($_POST['action'] == 'agree'){
                // Create PayPal request
                if(!file_exists(ROOT_PATH . '/modules/Resources/paypal.php')){
                    $error = $resource_language->get('resources', 'paypal_not_configured');
                } else {
                    $_SESSION['resource_purchasing'] = $resource->id;

                    $currency = DB::getInstance()->get('settings', ['name', '=', 'resources_currency']);
                    if (!$currency->count()) {
                        DB::getInstance()->insert('settings', [
                            'name' => 'resources_currency',
                            'value' => 'GBP'
                        ]);
                        $currency = 'GBP';

                    } else {
                        $currency = Output::getClean($currency->first()->value);
                    }

                    // Get author's PayPal
                    $author_paypal = DB::getInstance()->get('resources_users_premium_details', ['user_id', '=', $resource->creator_id]);
                    if (!$author_paypal->count() || !strlen($author_paypal->first()->paypal_email)){
                        $error = $resource_language->get('resources', 'author_doesnt_have_paypal');

                    } else {
                        $author_paypal = Output::getClean($author_paypal->first()->paypal_email);

                        require_once(ROOT_PATH . '/modules/Resources/paypal.php');

                        $payer = new \PayPal\Api\Payer();
                        $payer->setPaymentMethod('paypal');

                        $payee = new \PayPal\Api\Payee();
                        $payee->setEmail($author_paypal);

                        $amount = new \PayPal\Api\Amount();
                        $amount->setTotal($resource->price, $resource->discount);
                        $amount->setCurrency($currency);

                        $transaction = new \PayPal\Api\Transaction();
                        $transaction->setAmount($amount);
                        $transaction->setPayee($payee);
                        $transaction->setDescription(Output::getClean($resource->name));

                        $redirectUrls = new \PayPal\Api\RedirectUrls();
                        $redirectUrls->setReturnUrl(rtrim(Util::getSelfURL(), '/') . URL::build('/resources/purchase/' . Output::getClean($resource->id . '-' . Util::stringToURL($resource->name)) . '/', 'do=complete'))
                            ->setCancelUrl(rtrim(Util::getSelfURL(), '/') . URL::build('/resources/purchase/' . Output::getClean($resource->id . '-' . Util::stringToURL($resource->name)) . '/', 'do=cancel'));

                        $payment = new \PayPal\Api\Payment();
                        $payment->setIntent('sale')
                            ->setPayer($payer)
                            ->setTransactions([$transaction])
                            ->setRedirectUrls($redirectUrls);

                        try {
                            $payment->create($apiContext);

                            Redirect::to($payment->getApprovalLink());

                        } catch (\PayPal\Exception\PayPalConnectionException $ex) {
                            ErrorHandler::logCustomError($ex->getData());
                            $error = $resource_language->get('resources', 'error_while_purchasing');

                        }
                    }
                }
            }

        } else
            $error = $language->get('general', 'invalid_token');
    }
}

$page_title = $resource_language->get('resources', 'purchasing_resource_x', ['resource' => Output::getClean($resource->name)]);
require_once(ROOT_PATH . '/core/templates/frontend_init.php');

if(isset($_GET['do'])){
    if($_GET['do'] == 'complete'){
        $smarty->assign([
            'PURCHASING_RESOURCE' => $resource_language->get('resources', 'purchasing_resource_x', ['resource' => Output::getClean($resource->name)]),
            'PURCHASE_COMPLETE' => $resource_language->get('resources', 'purchase_complete'),
            'BACK_LINK' => URL::build('/resources/resource/' . Output::getClean($resource->id . '-' . Util::stringToURL($resource->name))),
            'BACK' => $language->get('general', 'back')
        ]);

        $template_file = 'resources/purchase_pending.tpl';
    } else {
        $smarty->assign([
            'PURCHASING_RESOURCE' => $resource_language->get('resources', 'purchasing_resource_x', ['resource' => Output::getClean($resource->name)]),
            'PURCHASE_CANCELLED' => $resource_language->get('resources', 'purchase_cancelled'),
            'BACK_LINK' => URL::build('/resources/resource/' . Output::getClean($resource->id . '-' . Util::stringToURL($resource->name))),
            'BACK' => $language->get('general', 'back')
        ]);

        $template_file = 'resources/purchase_cancelled.tpl';
    }

} else {
    $pre_purchase_info = DB::getInstance()->get('privacy_terms', ['name', '=', 'resource']);
    if (!$pre_purchase_info->count()) {
        $pre_purchase_info = '<p>You will be redirected to PayPal to complete your purchase.</p><p>Access to the download will only be granted once the payment has been completed, this may take a while.</p><p>Please note, ' . SITE_NAME . ' can\'t take any responsibility for purchases that occur through our resources section. If you experience any issues with the resource, please contact the resource author directly.</p><p>If your access to ' . SITE_NAME . ' is revoked (for example, your account is banned), you will lose access to any purchased resources.</p>';

        DB::getInstance()->insert('privacy_terms', [
            'name' => 'resource',
            'value' => $pre_purchase_info
        ]);
    } else
        $pre_purchase_info = Output::getPurified($pre_purchase_info->first()->value);

    // Assign Smarty variables
    $smarty->assign([
        'PURCHASING_RESOURCE' => $resource_language->get('resources', 'purchasing_resource_x', ['resource' => Output::getClean($resource->name)]),
        'CANCEL' => $language->get('general', 'cancel'),
        'CONFIRM_CANCEL' => $language->get('general', 'confirm_cancel'),
        'CANCEL_LINK' => URL::build('/resources/resource/' . Output::getClean($resource->id . '-' . Util::stringToURL($resource->name))),
        'PRE_PURCHASE_INFO' => $pre_purchase_info,
        'PURCHASE' => $resource_language->get('resources', 'purchase'),
        'TOKEN' => Token::get()
    ]);

    $template_file = 'resources/purchase.tpl';
}

if(Session::exists('purchase_resource_error'))
    $error = Session::flash('purchase_resource_error');

if(isset($error))
    $smarty->assign('ERROR', $error);

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');

$template->displayTemplate($template_file, $smarty);