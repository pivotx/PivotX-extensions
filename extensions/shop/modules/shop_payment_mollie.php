<?php
/**
 * Payment pages for iDEAL via mollie.nl payments.
 */

// include the classfiles for mollie
if($PIVOTX['config']->get('shop_enabled')!=false) {
    
    $shopbasedir = dirname(dirname(__FILE__));
    $shop_use_payment = $PIVOTX['config']->get('shop_use_payment', 'no');
    $has_payment_mollie_ideal = (stristr($shop_use_payment, 'mollie'))?1:0;

    if($has_payment_mollie_ideal && !class_exists('iDEAL_Payment')) {
        $idealfile = $shopbasedir."/providers/mollie/ideal-php5/ideal.class.php";
    
        if (file_exists($idealfile)) {
            require_once($idealfile);
            //debug('mollie ideal class loaded');
        } else {
            $PIVOTX['config']->set('shop_enabled', false);
            $logmessage = $PIVOTX['config']->get('shop_last_errors');
            $logmessage .= '|mollie ideal class missing';
            $PIVOTX['config']->set('shop_last_errors', $logmessage);        
            debug('mollie ideal class missing');
        }
    
        if (!in_array('ssl', stream_get_transports())) {
            $PIVOTX['config']->set('shop_enabled', false);
            $logmessage = $PIVOTX['config']->get('shop_last_errors');
            $logmessage .= '|ssl stream support missing';
            $PIVOTX['config']->set('shop_last_errors', $logmessage);  
            echo "<h1>Mollie iDEAL API Foutmelding</h1>";
            echo "<p>Uw PHP installatie heeft geen SSL ondersteuning. SSL is nodig voor de communicatie met de Mollie iDEAL API.</p>";
            exit;	
        }
    }
}

/**
 * Add the iDEAL via mollie.nl payments to the payment methods in the checkout form
 */
$this->addHook(
    'shop_payment_methods',
    'callback',
    '_mollie_payment_methods'
    );

function _mollie_payment_methods(&$defaultvalues) {
    global $PIVOTX, $shop_config;
    
	$shop_use_payment = $PIVOTX['config']->get('shop_use_payment', 'no');
	$has_payment_mollie_ideal = (stristr($shop_use_payment, 'mollie'))?1:0;

    if($has_payment_mollie_ideal) {
        $defaultvalues['payment_provider']['options']['mollie'] = array(
                        'label' => st('iDEAL'),
                        'value' => 'mollie',
                        'text' => st('Continue to pay using iDEAL via Mollie.nl'),
                        'charge_incl_tax' => 0,
                        'charge_excl_tax' => 0,
                        'tax_amount' => 0
                    );
    }
}

/**
 * Add the iDEAL via mollie.nl payments option to the available payment methods
 */
$this->addHook(
    'shop_admin_payment_options',
    'callback',
    '_mollie_admin_payment_options'
    );

function _mollie_admin_payment_options(&$payment_options) {
    if(!array_key_exists('mollie')) {
        $payment_options['mollie'] = st('iDEAL via Mollie.nl');
    }
}

/**
 * Add report page variable transform
 *
 * because all banks use different names
 */
$this->addHook(
    '_shop_payment_report_variables',
    'callback',
    '_mollie_payment_report_variables'
    );

function _mollie_payment_report_variables(&$reportvariables) {
    if($reportvariables['orderID']) {
        $reportvariables['order_id'] = $reportvariables['orderID'];
    }
}


/**
 * Add return page variable transform
 *
 * because all banks use different names
 */
$this->addHook(
    '_shop_payment_return_variables',
    'callback',
    '_mollie_payment_return_variables'
    );

function _mollie_payment_return_variables(&$returnvariables) {

}


/**
 * Add the mollie.nl options and settings to the admin form
 */
$this->addHook(
    'shop_admin_configkeys',
    'callback',
    '_mollie_admin_configkeys'
    );

function _mollie_admin_configkeys(&$shop_configkeys) {
	$mollie_configkeys = array(
		'shop_mollie_testmode',
		'shop_mollie_partner_key',
		'shop_mollie_profile_key',
		'shop_mollie_return_url',
		'shop_mollie_report_url',
		'shop_email_mollie_return_tpl'
	);
    $shop_configkeys = array_merge($shop_configkeys, $mollie_configkeys);
}
/**
 * Add the iDEAL via mollie.nl payments options and settings in the administration form
 */
$this->addHook(
    'shop_admin_payment',
    'callback',
    '_mollie_admin_payment'
    );

function _mollie_admin_payment(&$form) {
    global $PIVOTX, $shop_config;
    
    //debug('form in mollie callback');
    //debug_printr($form);
    //debug('shop_config in mollie callback');
    //debug_printr($shop_config);
    
	$form->add( array(
        'type' => "hr"
    ));
    
    
    
    $shop_use_payment = $PIVOTX['config']->get('shop_use_payment', 'no');
	$has_payment_mollie_ideal = (stristr($shop_use_payment, 'mollie'))?1:0;
    if($has_payment_mollie_ideal) {
    
        if($shop_config['shop_currency'] == 'EUR') {
            $form->add( array(
                'type' => 'custom',
                'text' => sprintf("<tr><td colspan='2'><h3>%s</h3> <em>(%s)</em></td></tr>",
                    st('Mollie.nl settings'),
                    st('You need a registration at <a href="http://www.mollie.nl/" target="_blank">mollie.nl</a> to use this!') )
            ));
    
            $form->add( array(
                'type' => 'checkbox',
                'name' => 'shop_mollie_testmode',
                'label' => st('Mollie.nl testmode'),
                'text' => makeJtip(st('Mollie.nl testmode'), st('If the testmode is active no transactions will go through. Turn this off for production sites.')),
            ));
        
            $form->add( array(
                'type' => 'text',
                'name' => 'shop_mollie_partner_key',
                'isrequired' => 1,
                'label' => st('Mollie.nl partner key'),
            ));
        
            $form->add( array(
                'type' => 'text',
                'name' => 'shop_mollie_profile_key',
                'isrequired' => 1,
                'label' => st('Mollie.nl profile key'),
            ));
        
            $form->add( array(
                'type' => 'text',
                'name' => 'shop_mollie_return_url',
                'isrequired' => 1,
                'label' => st('Mollie.nl return url'),
            ));
        
            $form->add( array(
                'type' => 'text',
                'name' => 'shop_mollie_report_url',
                'isrequired' => 1,
                'label' => st('Mollie.nl report url'),
            ));

            $templatename_shop_email_mollie_return_tpl = dirname(dirname(__FILE__)) .'/'. $shop_config['shop_email_mollie_return_tpl'];
            if(!file_exists($templatename_shop_email_mollie_return_tpl)) {
                $form->add( array(
                    'type' => 'custom',
                    'text' => sprintf("<tr><td colspan='2'><label for='shop_email_mollie_return_tpl' class='error'>%s</label></td></tr>",
                        st('iDEAL mail template') . ' ' . st('was not found at this location') )
                ));
                // turn the shop off
                $PIVOTX['config']->set('shop_enabled', false);
                $logmessage = $PIVOTX['config']->get('shop_last_errors');
                $logmessage .= '|iDEAL mail template missing';
                $PIVOTX['config']->set('shop_last_errors', $logmessage);  
            }
            
            $form->add( array(
                'type' => 'text',
                'name' => 'shop_email_mollie_return_tpl',
                'isrequired' => 1,
                'label' => st('iDEAL mail template'),
                'error' => st('That\'s not a proper template name!'),
                'size' => 50,
                'validation' => 'string|minlen=2|maxlen=60',
                'text' => makeJtip(st('iDEAL mail template'), st('The mail template for iDEAL payment messages. The templates are located in the extension direcotry. (usually templates/name_of_template.tpl).')),
            ));
    
        } else {
            $form->add( array(
                'type' => 'custom',
                'text' => sprintf("<tr><td colspan='2'><h3>%s</h3> <em>(%s)</em></td></tr>",
                    st('Mollie.nl settings'),
                    st('Mollie.nl payments are unavailable if the currency is not set to &euro;. Set the currency to &euro; and reload this page to use mollie.nl payments.') )
            ));	
        }
    }
    
}

/**
 * Add the shop_return_mail for the email template
 */
$this->addHook(
    'shop_return_mail',
    'callback',
    '_mollie_return_mail'
    );

function _mollie_return_mail(&$mailtemplates) {
	$mailtemplates['mollie_return_tpl'] = 'shop_email_mollie_return_tpl';
}

/**
 * Add the _prepare_payment hook
 */
$this->addHook(
    '_mollie_prepare_payment',
    'callback',
    '_mollie_prepare_payment'
    );

/**
 * Setup page for iDEAL via mollie.nl payments
 *
 * Choose a bank
 */
function _mollie_prepare_payment(&$orderparms) {
    global $PIVOTX;
    
    //debug_printr($orderparms);
    $order = $PIVOTX['order'];
    //debug_printr($order);
    $ordertotals = $order->getOrderTotals();
    //debug_printr($ordertotals);
    $order_id = $order->getOrderId();
    $order_details = $order->getOrderDetails();
    
    $ordertotals['payment_message'] = st('Bestelling bij') .' '. $PIVOTX['config']->get("shop_email_name", 'test');
    //debug_printr($_POST);
    
    $shop_config['shop_mollie_partner_key'] = $PIVOTX['config']->get('shop_mollie_partner_key', 'test');
    $shop_config['shop_mollie_profile_key'] = $PIVOTX['config']->get('shop_mollie_profile_key', 'test');
    $shop_config['shop_mollie_return_url'] = $PIVOTX['config']->get('shop_mollie_return_url', 'index.php?action=return');
    $shop_config['shop_mollie_report_url'] = $PIVOTX['config']->get('shop_mollie_report_url', 'index.php?action=report');
    
    if (!stristr($shop_config['shop_mollie_return_url'], $PIVOTX['paths']['canonical_host'])) {    
        $shop_config['shop_mollie_return_url'] = $PIVOTX['paths']['canonical_host']
            . $PIVOTX['paths']['site_url']
            . $shop_config['shop_mollie_return_url'];
    }
    if (!stristr($shop_config['shop_mollie_report_url'],$PIVOTX['paths']['canonical_host'])) {    
        $shop_config['shop_mollie_report_url'] = $PIVOTX['paths']['canonical_host']
            . $PIVOTX['paths']['site_url']
            . $shop_config['shop_mollie_report_url'];
    }
    
    if($shop_config['shop_mollie_partner_key']=='test'
       ||$shop_config['shop_mollie_profile_key']=='test'
       ||empty($shop_config['shop_mollie_partner_key'])
       ||empty($shop_config['shop_mollie_profile_key'])
       ||empty($shop_config['shop_mollie_return_url'])
       ||empty($shop_config['shop_mollie_report_url'])
       ) {
        $page['title'] = 'Betaal via iDEAL';
        $page['body'] = '<p>De iDEAL configuratie klopt helaas niet, betalen is niet mogelijk.</p>';
        return $page;
    }
    
    $iDEAL = new iDEAL_Payment ($shop_config['shop_mollie_partner_key']);
    $iDEAL->setProfileKey($shop_config['shop_mollie_profile_key']);
    
    if($PIVOTX['config']->get('shop_mollie_testmode')) {
        $iDEAL->setTestMode();
        debug('Mollie testmode is active');
    }

    if (isset($orderparms['bank_id']) && !empty($orderparms['bank_id'])) {
        if($PIVOTX['config']->get('shop_mollie_testmode')) {
            debug_printr(
                array(
                    'bank id' => $orderparms['bank_id'],
                    'totaalbedrag in centen' => $ordertotals['cumulative_incl_tax'],
                    'omschrijving' => $ordertotals['payment_message'],
                    'mollie return url' => $shop_config['shop_mollie_return_url'],
                    'mollie report url' => $shop_config['shop_mollie_report_url']
                )
            );
        }
        if ($iDEAL->createPayment($orderparms['bank_id'], $ordertotals['cumulative_incl_tax'], $ordertotals['payment_message'], $shop_config['shop_mollie_return_url'], $shop_config['shop_mollie_report_url'])) {
            /* Hier kunt u de aangemaakte betaling opslaan in uw database, bijv. met het unieke transactie_id
               Het transactie_id kunt u aanvragen door $iDEAL->getTransactionId() te gebruiken. Hierna wordt 
               de consument automatisch doorgestuurd naar de gekozen bank. */
            
            $saveorder = $order_details;
            $saveorder['order_id'] = $order_id;
            $saveorder['payment_amount_total'] = $ordertotals['cumulative_incl_tax'];
            $saveorder['payment_message'] = $ordertotals['payment_message'];
            //$saveorder['payment_provider'] = 'Mollie.nl iDEAL';
            $saveorder['payment_external_code'] = $iDEAL->getTransactionId();

            if($PIVOTX['config']->get('shop_mollie_testmode')) {
                debug('new payment_external_code: '.$saveorder['payment_external_code']);
            }

            $saveorder['payment_datetime'] = date("Y-m-d H:i:s", time());
            $saveorder['payment_status'] = 'redirected';
            if($PIVOTX['config']->get('shop_mollie_testmode')) {
                debug_printr($saveorder);
            }

            $order = _shop_save_order($saveorder);
            if($PIVOTX['config']->get('shop_mollie_testmode')) {
                debug_printr($order);
            }
        
            header("Location: " . $iDEAL->getBankURL());
            exit;	
        } else {
            /* Er is iets mis gegaan bij het aanmaken bij de betaling. U kunt meer informatie 
               vinden over waarom het mis is gegaan door $iDEAL->getErrorMessage() en/of 
               $iDEAL->getErrorCode() te gebruiken. */
            $output .= '<p>'.st('The payment could not be initialized.').'</p>';
            $output .= '<p><strong>'. st('Error').':</strong> '. $iDEAL->getErrorMessage(). '</p>';
            $output .= '<pre>'. print_r($order, true) .'</pre>';

        }
    }
    
    /*
      Hier worden de mogelijke banken opgehaald en getoont aan de consument.
    */
    $bank_array = $iDEAL->getBanks();
    
    if ($bank_array == false)  {
        $output .= '<p>Er is een fout opgetreden bij het ophalen van de banklijst: '. $iDEAL->getErrorMessage(). '</p>';

    }
    $mollie_introtext = '<p>'. st('Kies in het volgende formulier uw bank en klik op betalen.') .'</p>';
    $mollie_introtext .= '<p>'. st('Hierna wordt u doorgestuurd naar de website van uw bank voor de betaling.') .'</p>';
    $mollie_introtext .= '<p>'. st('Na de betaling komt u weer terug op deze site met een overzicht van uw bestelling en betaling.') .'</p>';
    $mollie_introtext .= '<p>'. st('Pas als u bij iDEAL heeft betaald wordt de bestelling definitief.') .'</p>';

    $output .= <<<EOF
    <div id="checkoutform" class="mollie_bank_form">
    [[mollie_introtext]]
    <form method="post">
        <select name="bank_id">
            [[mollie_bank_options]]
        </select>
        <button type="submit" name="submit" class="button checkoutbutton ideal_button"><span>[[mollie_payment_action]]</span></button>
    </form>
    </div>
EOF;
    $options = '<option value="">Kies uw bank</option>';
    foreach ($bank_array as $bank_id => $bank_name) {
        $options .= '<option value="'.$bank_id.'">'.$bank_name .'</option>';
    }
    $output = str_replace('[[mollie_introtext]]', '<div class="mollie_introtekst">'.$mollie_introtext.'</div>', $output);
    $output = str_replace('[[mollie_payment_action]]', st('Betaal via iDEAL'), $output);
    $output = str_replace('[[mollie_bank_options]]', $options, $output);
    
    $page['title'] = 'Betaal via iDEAL';
    $page['body'] = $output;
    return $page;
}

/**
 * Add the return hook
 */
$this->addHook(
    '_mollie_report_page',
    'callback',
    '_mollie_report_page'
    );

/**
 * background page where iDEAL via mollie.nl payments reports transaction status
 */
function _mollie_report_page($orderandparams) {
    global $PIVOTX;
    
    $order = $orderandparams['order'];
    $params = $orderandparams['params'];
    
    $ordertotals = $order->getOrderTotals();
    //debug_printr($ordertotals);
    $order_id = $order->getOrderId();
    $order_details = $order->getOrderDetails();
    
    if($PIVOTX['config']->get('shop_mollie_testmode')) {
        debug('starting transaction report: '.$order_details['transaction_id']);
    }

    $shop_config['shop_mollie_partner_key'] = $PIVOTX['config']->get('shop_mollie_partner_key', 'test');
    $shop_config['shop_mollie_profile_key'] = $PIVOTX['config']->get('shop_mollie_profile_key', 'test');
    $shop_config['shop_mollie_return_url'] = $PIVOTX['config']->get('shop_mollie_return_url', 'index.php?action=return');
    $shop_config['shop_mollie_report_url'] = $PIVOTX['config']->get('shop_mollie_report_url', 'index.php?action=report');
    
    /**
     *
    Status	Omschrijving
    Success	De betaling is gelukt
    Cancelled	De consument heeft de betaling geannuleerd.
    Failure	De betaling is niet gelukt (er is geen verdere informatie beschikbaar)
    Expired	De betaling is verlopen doordat de consument niets met de betaling heeft gedaan.
    CheckedBefore	U heeft de betalingstatus al een keer opgevraagd.
     *
     */

    if (isset($order_details['payment_external_code']) && ($params['transaction_id']==$order_details['payment_external_code'])) {
        $iDEAL = new iDEAL_Payment ($shop_config['shop_mollie_partner_key']);
        $iDEAL->setProfileKey($shop_config['shop_mollie_profile_key']);
        if($PIVOTX['config']->get('shop_mollie_testmode')) {
            $iDEAL->setTestMode();
            debug('Mollie testmode is active');
        }
        
        $iDEAL->checkPayment($order_details['payment_external_code']);
        $payment_external_code = $iDEAL->getTransactionId();
        if($PIVOTX['config']->get('shop_mollie_testmode')) {
            debug('transaction ids: ' . $payment_external_code . " = " . $_GET['transaction_id']);
            //debug('poging een met $payment_external_code: ' . $payment_external_code);
        }

        if($PIVOTX['config']->get('shop_mollie_testmode')) {
            debug_printr($order_details);
        }
    
        if ($iDEAL->getPaidStatus() == true) {
            
            /* De betaling is betaald, deze informatie kan opgeslagen worden (bijv. in de database).
               Met behulp van $iDEAL->getConsumerInfo(); kunt u de consument gegevens ophalen (de 
               functie returned een array). Met behulp van $iDEAL->getAmount(); kunt u het betaalde
               bedrag vergelijken met het bedrag dat afgerekend zou moeten worden. */
            if($order_details['payment_amount_total'] == $iDEAL->getAmount()) {
                $order_details['order_status'] = 'complete';
                $order_details['payment_datetime'] = date("Y-m-d H:i:s", time());
                $order_details['payment_message'] = $order_details['payment_message'] . "\n". $iDEAL->getBankStatus() ." - " .$iDEAL->getErrorCode() . ':'. $iDEAL->getErrorMessage();
                $order_details['payment_status'] = $iDEAL->getBankStatus() ;
                $order_details = _shop_save_order($order_details);
            } else {
                $order_details['order_status'] = 'waiting';
                $bankstatus = $iDEAL->getBankStatus();
                switch($bankstatus) {
                    case 'Cancelled':
                        $order['order_status'] = 'cancelled';
                        break;
                    case 'Failure':
                        $order['order_status'] = 'error';
                        break;
                    case 'Expired':
                        $order['order_status'] = 'expired';
                        break;
                }
                
                $order_details['payment_datetime'] = date("Y-m-d H:i:s", time());
                $order_details['payment_message'] = $order_details['payment_message'] . "\n". $iDEAL->getBankStatus() ." - " . $iDEAL->getErrorCode() . ':'. $iDEAL->getErrorMessage() . "\n". 'Amount payed at bank is not the same as amount at site';
                $order_details['payment_status'] = $iDEAL->getBankStatus() ;
                $order_details = _shop_save_order($order_details);
            }
        } else {
            $order_details['order_status'] = 'waiting';
            $bankstatus = $iDEAL->getBankStatus();
            switch($bankstatus) {
                case 'Cancelled':
                    $order['order_status'] = 'cancelled';
                    break;
                case 'Failure':
                    $order['order_status'] = 'error';
                    break;
                case 'Expired':
                    $order['order_status'] = 'expired';
                    break;
            }
            $order_details['payment_datetime'] = date("Y-m-d H:i:s", time());
            $order_details['payment_message'] = $order_details['payment_message'] . "\n". $iDEAL->getBankStatus() ." - " . $iDEAL->getErrorCode() . ':'. $iDEAL->getErrorMessage();
            $order_details['payment_status'] = $iDEAL->getBankStatus();
            $order_details = _shop_save_order($order_details);
        }
        if($PIVOTX['config']->get('shop_mollie_testmode')) {
            debug(var_export($iDEAL, true));
        }
        
        // drop a mail with instructions
        //debug('shop mollie mail default sending');
        //debug(var_export($order, true));
        $order_details = _shop_order_mail_default('mollie_return_tpl', $order_details);
        //debug('shop mollie mail default sent');
    }
    // print minimal output as required by the api
    print 'OK';
    if($PIVOTX['config']->get('shop_mollie_testmode')) {
        debug('done transaction return: '.$_GET['transaction_id']);
    }
    exit;
}


/**
 * Add the return hook
 */
$this->addHook(
    '_mollie_return_page',
    'callback',
    '_mollie_return_page'
    );

/**
 * return page where mollie sends customers after payment
 */
function _mollie_return_page($orderandparams) {
    global $PIVOTX;
    
    $order = $orderandparams['order'];
    $params = $orderandparams['params'];
    
    //debug_printr($orderparms);
    //$order = $PIVOTX['order'];
    //debug_printr($order);
    $ordertotals = $order->getOrderTotals();
    //debug_printr($ordertotals);
    $order_id = $order->getOrderId();
    $order_details = $order->getOrderDetails();
    
    if ($order_details['order_public_code']) {
        /*
          Via report.php heeft Mollie de betaling al gemeld, en in dat script heeft bij Mollie gecontroleerd 
          wat de betaalstatus is. Deze betaalstatus is in report.php ergens opgeslagen in het systeem (bijv. 
          in de database).
         
          De klant komt bij dit script terug na de betaling.
        */
        if($order_details['order_status']=='complete'&&$order_details['payment_status']==true) {
            $title = st('Thanks');
            $output = st('Thank you for your order.') . ' '
                      .st('Your payment is confirmed.') . ' '
                      .st('You will get a message with the confirmation soon.');
        } elseif($order_details['payment_provider']!='other') {
            $title = st('Thanks');
            $output = st('Thank you for your order.') . ' '
                      . st('Your payment is not yet confirmed (by iDEAL).') . ' '
                      . st('As soon as your payment is confirmed you will receive a message.');
        } else {
            $title = st('Thanks');
            $output = st('Your order is received.') . st('You will receive a message with further instructions for payment.');
        }

        $params['title'] = $title;
        if($order_details) {
            $params['body'] = '<p>'. $output .'</p>';
            $params['body'] .= _shop_order_summary($order_details);
        } else {
            $params['body'] = '<p>'. $output .'</p>';
        }

        // drop a mail with instructions
        $order_details = _shop_order_mail_default($order_details['payment_provider'].'_return_tpl', $order_details);
		
        $return_url = $PIVOTX['config']->get('shop_default_homepage', '/index.php?w=shop');
        $params['body'] .= '<p><a href="'.$return_url.'" class="continue_shopping">'. st('Continue shopping') .'</a></p>';
    } else {
        $params['title'] = st('Error');
        $params['body'] = '<p>'. st('No order found.') . '</p>';
        $params['body'] .= '<p><a href="'.$return_url.'" class="continue_shopping">'. st('Continue shopping') .'</a></p>';
    }
    
    // ensure that the page has the right modifier for shop_action
    $params['action'] = 'return';
    return $params;

}


/**
 * Add the paymentinfo hook
 */
$this->addHook(
    '_mollie_payment_info',
    'callback',
    '_mollie_payment_info'
    );

function _mollie_payment_info($label) {
    if ($label=='mollie') {
        return '<span class="ideal_label">iDEAL</span> met <a href="http://www.mollie.nl" class="mollie_label">mollie.nl</a>';
    }
    return $label;
    
}