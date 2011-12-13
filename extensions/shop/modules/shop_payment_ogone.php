<?php
/**
 * Payment pages for iDEAL via ogone.nl payments.
 */

/**
 * Add the iDEAL via ogone.nl payments to the payment methods in the checkout form
 */
$this->addHook(
    'shop_payment_methods',
    'callback',
    '_ogone_payment_methods'
    );

function _ogone_payment_methods(&$defaultvalues) {
    global $PIVOTX, $shop_config;
    
	$shop_use_payment = $PIVOTX['config']->get('shop_use_payment', 'no');
	$has_payment_ogone_ideal = (stristr($shop_use_payment, 'ogone'))?1:0;

    if($has_payment_ogone_ideal) {
        $defaultvalues['payment_provider']['options']['ogone'] = array(
                        'label' => _ogone_payment_info('ogone'),
                        'value' => 'ogone',
                        'text' => st('Continue to pay using iDEAL via Ogone.nl'),
                        'charge_incl_tax' => 0,
                        'charge_excl_tax' => 0,
                        'tax_amount' => 0
                    );
    }
}

/**
 * Add the iDEAL via ogone.nl payments option to the available payment methods
 */
$this->addHook(
    'shop_admin_payment_options',
    'callback',
    '_ogone_admin_payment_options'
    );

function _ogone_admin_payment_options(&$payment_options) {
    if(!array_key_exists('ogone', $payment_options)) {
        $payment_options['ogone'] = st('iDEAL via Ogone.nl, Rabobank or ABN AMRO');
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
    '_ogone_payment_report_variables'
    );

function _ogone_payment_report_variables(&$reportvariables) {
    if($reportvariables['orderID']) {
        $reportvariables['order_id'] = $reportvariables['orderID'];
    }
    if($reportvariables['PAYID']) {
        $reportvariables['transaction_id'] = $reportvariables['PAYID'];
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
    '_ogone_payment_return_variables'
    );

function _ogone_payment_return_variables(&$returnvariables) {
    if($returnvariables['orderID']) {
        $returnvariables['order_id'] = $returnvariables['orderID'];
    }
    if($returnvariables['PAYID']) {
        $returnvariables['transaction_id'] = $returnvariables['PAYID'];
    }
}

/**
 * Add the ogone.nl options and settings to the admin form
 */
$this->addHook(
    'shop_admin_configkeys',
    'callback',
    '_ogone_admin_configkeys'
    );

function _ogone_admin_configkeys(&$shop_configkeys) {
	$ogone_configkeys = array(
		'shop_ogone_testmode',
        'shop_ogone_pspid',
        'shop_ogone_provider',
        'shop_ogone_return_url',
        'shop_ogone_report_url',
        'shop_ogone_sha1',
        'shop_ogone_sha1out',
        'shop_ogone_sha1in',
        'shop_ogone_encryption',
		'shop_email_ogone_return_tpl'
	);
    $shop_configkeys = array_merge($shop_configkeys, $ogone_configkeys);
}
/**
 * Add the iDEAL via ogone.nl payments options and settings in the administration form
 */
$this->addHook(
    'shop_admin_payment',
    'callback',
    '_ogone_admin_payment'
    );

function _ogone_admin_payment(&$form) {
    global $PIVOTX, $shop_config;
    
    //debug('form in ogone callback');
    //debug_printr($form);
    //debug('shop_config in ogone callback');
    //debug_printr($shop_config);
    
	$form->add( array(
        'type' => "hr"
    ));
    
    
    
    $shop_use_payment = $PIVOTX['config']->get('shop_use_payment', 'no');
	$has_payment_ogone_ideal = (stristr($shop_use_payment, 'ogone'))?1:0;
    if($has_payment_ogone_ideal) {

        $form->add( array(
            'type' => 'custom',
            'text' => sprintf("<tr><td colspan='2'><h3>%s</h3> <em>(%s)</em></td></tr>",
                st('Ogone.nl settings'),
                st('You need an account at <a href="http://www.ogone.nl/" target="_blank">ogone.nl</a>, <a href="https://i-kassa.rabobank.nl/">Rabobank</a> or <a href="https://internetkassa.abnamro.nl/">ABN AMRO</a> to use this!') )
        ));

        $form->add( array(
            'type' => 'checkbox',
            'name' => 'shop_ogone_testmode',
            'label' => st('Ogone.nl testmode'),
            'text' => makeJtip(st('Ogone.nl testmode'), st('If the testmode is active no transactions will go through. Turn this off for production sites.')),
        ));
        
        $providers = array(
            'ogone' => 'ogone.nl',
            'abnamro' => 'ABN AMRO bank',
            'rabobank' => 'Rabobank'
        );
        
        $form->add( array(
            'type' => 'select',
            'options' => $providers,
            'name' => 'shop_ogone_provider',
            'isrequired' => 1,
            'label' => st('Ogone provider'),
        ));
    
    
        $form->add( array(
            'type' => 'text',
            'name' => 'shop_ogone_pspid',
            'isrequired' => 1,
            'label' => st('PSPID'),
        ));
    
        $form->add( array(
            'type' => 'text',
            'name' => 'shop_ogone_sha1in',
            'isrequired' => 1,
            'label' => st('SHA-1-IN secret'),
        ));
        $form->add( array(
            'type' => 'text',
            'name' => 'shop_ogone_sha1out',
            'isrequired' => 1,
            'label' => st('SHA-1-OUT secret'),
        ));
 
        $encryptions = array(
            'sha1' => 'SHA-1',
            'sha512' => 'SHA-512 (veiliger)',
        );
        
        $form->add( array(
            'type' => 'select',
            'options' => $encryptions,
            'name' => 'shop_ogone_encryption',
            'isrequired' => 1,
            'label' => st('Encryption'),
        ));
        
        $form->add( array(
            'type' => 'text',
            'name' => 'shop_ogone_return_url',
            'isrequired' => 1,
            'label' => st('return url'),
        ));
        
        $form->add( array(
            'type' => 'text',
            'name' => 'shop_ogone_report_url',
            'isrequired' => 1,
            'label' => st('report url'),
        ));

        $templatename_shop_email_ogone_return_tpl = dirname(dirname(__FILE__)) .'/'. $shop_config['shop_email_ogone_return_tpl'];
        if(!file_exists($templatename_shop_email_ogone_return_tpl)) {
            $form->add( array(
                'type' => 'custom',
                'text' => sprintf("<tr><td colspan='2'><label for='shop_email_ogone_return_tpl' class='error'>%s</label></td></tr>",
                    st('iDEAL mail template') . ' ' . st('was not found at this location') )
            ));
            // turn the shop off
            $PIVOTX['config']->set('shop_enabled', false);
            $logmessage = $PIVOTX['config']->get('shop_last_errors');
            $logmessage .= '|ogone iDEAL mail template missing';
            $PIVOTX['config']->set('shop_last_errors', $logmessage);  
        }
        
        $form->add( array(
            'type' => 'text',
            'name' => 'shop_email_ogone_return_tpl',
            'isrequired' => 1,
            'label' => st('iDEAL mail template'),
            'error' => st('That\'s not a proper template name!'),
            'size' => 50,
            'validation' => 'string|minlen=2|maxlen=60',
            'text' => makeJtip(st('Ogone.nl mail template'), st('The mail template for iDEAL payment messages. The templates are located in the extension direcotry. (usually templates/name_of_template.tpl).')),
        ));

    }
    
}

/**
 * Add the shop_return_mail for the email template
 */
$this->addHook(
    'shop_return_mail',
    'callback',
    '_ogone_return_mail'
    );

function _ogone_return_mail(&$mailtemplates) {
	$mailtemplates['ogone_return_tpl'] = 'shop_email_ogone_return_tpl';
}

/**
 * Add the _prepare_payment hook
 */
$this->addHook(
    '_ogone_prepare_payment',
    'callback',
    '_ogone_prepare_payment'
    );

/**
 * Setup page for iDEAL via ogone.nl payments
 *
 * Choose a bank
 */
function _ogone_prepare_payment(&$orderparms) {
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

    $shop_config['shop_ogone_pspid'] = $PIVOTX['config']->get('shop_ogone_pspid', 'test');
    $shop_config['shop_ogone_provider'] = $PIVOTX['config']->get('shop_ogone_provider', 'ogone');
    
    $shop_config['shop_ogone_provider_address'] = _ogone_urls($shop_config['shop_ogone_provider'], $PIVOTX['config']->get('shop_ogone_testmode'));
    $shop_config['shop_ogone_return_url'] = $PIVOTX['config']->get('shop_ogone_return_url', 'index.php?action=return');
    $shop_config['shop_ogone_report_url'] = $PIVOTX['config']->get('shop_ogone_report_url', 'index.php?action=report');
    $shop_config['shop_ogone_accept_url'] = $shop_config['shop_ogone_return_url'] . '&status=accept';
    $shop_config['shop_ogone_decline_url'] = $shop_config['shop_ogone_return_url'] . '&status=decline';
    $shop_config['shop_ogone_exception_url'] = $shop_config['shop_ogone_return_url'] . '&status=exception';
    $shop_config['shop_ogone_cancel_url'] = $shop_config['shop_ogone_return_url'] . '&status=cancel';
    //$shop_config['shop_ogone_sha1'] = $PIVOTX['config']->get('shop_ogone_sha1in', 'test');
    $shop_config['shop_ogone_sha1in'] = $PIVOTX['config']->get('shop_ogone_sha1in', 'test');
    $shop_config['shop_ogone_sha1out'] = $PIVOTX['config']->get('shop_ogone_sha1out', 'test');
    $shop_config['shop_ogone_encryption'] = $PIVOTX['config']->get('shop_ogone_encryption', 'sha1');
    
    if($PIVOTX['config']->get('shop_ogone_testmode')) {
        debug('Ogone testmode is active');
    }
    $output = '<div class="shop_autorefreshmessage"><p>'. st('Please wait until you are redirected.') .'</p><p>'. st('If you are not redirected you may click on the continue button.') .'</p></div>';
    
    $shop_configkeys = array_keys($shop_config);
    foreach($shop_configkeys as $key) {
        //$output .= $key . "<br />";
        if(stristr($key, '_url') && !stristr($shop_config[$key], $PIVOTX['paths']['canonical_host'])) {
            $shop_config[$key] = $PIVOTX['paths']['canonical_host']
                . $PIVOTX['paths']['site_url']
                . $shop_config[$key];
        }
    }

    if($shop_config['shop_ogone_pspid']=='test'
       ||$shop_config['shop_ogone_sha1in']=='test'
       ||$shop_config['shop_ogone_sha1out']=='test'
       ||empty($shop_config['shop_ogone_return_url'])
       ||empty($shop_config['shop_ogone_accept_url'])
       ) {
        $page['title'] = st('Payment');
        $page['body'] = '<p>De ogone configuratie klopt helaas niet, betalen is niet mogelijk.</p>';
        return $page;
    }
    
    
    $ogone_fields = _ogone_fields();
    if($PIVOTX['config']->get('shop_ogone_testmode')) {
        $ogone_type = 'hidden';
    } else {
        $ogone_type = 'hidden';
    }
    $shastring = '';
    $language = $PIVOTX['config']->get('language', 'nl');
    foreach($ogone_fields as $key) {
        switch($key) {
            case 'PSPID': 
                $value = $shop_config['shop_ogone_pspid'];
                break;
            case 'orderID': 
                $value = $order_details['order_id'];
                break;
            case 'amount': 
                $value =  $order_details['totals']['cumulative_incl_tax'];
                break;
            case 'currency': 
                $value = $PIVOTX['config']->get('shop_currency');
                break;
            case 'language': 
                $value = 'nl_NL';
                break;
            case 'CN': 
                $value = $order_details['user_name'];
                break;
            case 'Name': 
                $value = $order_details['order_public_code'];
                break;
            case 'EMAIL': 
                $value = $order_details['user_email'];
                break;
            case 'owneraddress': 
                $value = $order_details['user_address'];
                break;
            case 'ownerZIP': 
                $value = $order_details['user_postcode'];
                break;
            case 'ownertown': 
                $value = $order_details['user_city'];
                break;
            case 'ownercty':
                switch(strtolower($order_details['user_country'])) {
                    case 'nederland':
                        $value = 'nl';
                        break;
                    case 'belgie':
                    case 'belgië':
                        $value = 'be';
                        break;
                    case 'duitsland':
                    case 'deutschland':
                        $value = 'de';
                        break;
                    case 'england':
                    case 'great britain':
                    case 'groot britannie':
                        $value = 'gb';
                        break;
                    case 'france':
                    case 'frankrijk':
                        $value = 'fr';
                        break;
                    default:
                        $value = strtolower($order_details['user_country']);
                        break;
                }
                break;
            case 'ownertelno': 
                $value = $order_details['user_phone'];
                break;
            case 'accepturl': 
                $value = $shop_config['shop_ogone_accept_url'];
                break;
            case 'declineurl': 
                $value = $shop_config['shop_ogone_decline_url'];
                break;
            case 'exceptionurl': 
                $value = $shop_config['shop_ogone_exception_url'];
                break;
            case 'cancelurl': 
                $value = $shop_config['shop_ogone_cancel_url'];
                break;
            default: 
                $value = $order_details[$key];
                break;
        }
        if($key!='SHASign') {
            
            if($value!='') {
                $shastring[] = strtoupper($key).'='.$value;
                $shastring_plain .= strtoupper($key).'='.$value.$shop_config['shop_ogone_sha1in'];
            }

            if(0 && $PIVOTX['config']->get('shop_ogone_testmode')) {
                $output .= '<label>' . $key;
                $output .= '<input type="'.$ogone_type.'" name="'.$key.'" size="60" value="'.$value.'" />';
                $output .= '</label><br />';
            } else {
                $output .= '<input type="'.$ogone_type.'" name="'.$key.'" size="60" value="'.$value.'" />';
            }
            $postvars[$key] = $value;
        }
    }
    
    
    $shastring = join($shop_config['shop_ogone_sha1in'], $shastring).$shop_config['shop_ogone_sha1in'];
    
    if(0 && $PIVOTX['config']->get('shop_ogone_testmode')) {
        $output .= '<pre>'. $shastring . '</pre><br />';
    }
    
    if($shop_config['shop_ogone_encryption'] == 'sha512') {
        $shaencoded = strtoupper(hash('sha512', $shastring));
    } else {
        $shaencoded = strtoupper(sha1($shastring));
    }

    if(0 && $PIVOTX['config']->get('shop_ogone_testmode')) {
        $output .= '<label>' . 'SHASign';
        $output .= '<input type="'.$ogone_type.'" name="'. 'SHASign'.'" size="60" value="'.$shaencoded.'" />';
        $output .= '</label><br />';
    } else {
        $output .= '<input type="'.$ogone_type.'" name="'. 'SHASign'.'" size="60" value="'.$shaencoded.'" />';
    }
    $postvars['SHASign'] = $shaencoded;

    $output .= '<button type="submit" value="" name="submit2">'. st('Continue to bank payment page') .'</button>';
    //$output .= '<input type="submit" value="" name="submit2" />';
    
    $output = '<form method="post" class="shop_autorefreshform" action="'.$shop_config['shop_ogone_provider_address'].'">' . $output . '</form>';

    if(0 && $PIVOTX['config']->get('shop_ogone_testmode')) {
        $output .= '<h2>shop_config</h2><pre>'.htmlspecialchars(print_r($shop_config, true)).'</pre> ';
        $output .= '<h2>postvars</h2><pre>'.htmlspecialchars(print_r($postvars, true)).'</pre> ';
        $output .= '<h2>order</h2><pre>'.htmlspecialchars(print_r($order, true)).'</pre> ';
        $output .= '<h2>order_id</h2><pre>'.htmlspecialchars(print_r($order_id, true)).'</pre> ';
        $output .= '<h2>ordertotals</h2><pre>'.htmlspecialchars(print_r($ordertotals, true)).'</pre> ';
        $output .= '<h2>order_details</h2><pre>'.htmlspecialchars(print_r($order_details, true)).'</pre> ';
    }

    $page['title'] = st('Payment');
    $page['body'] = $output;
    return $page;
}

function _ogone_fields() {
    $fields = array(
        // user, account and orderinfo
        'PSPID', // Your affiliation name in our system
        'orderID', // Your order number (merchant reference). The system checks that a payment has not been requested twice for the same order. The orderID has to be assigned dynamically.
        'amount', // Amount to be paid MULTIPLIED BY 100 since the format of the amount must not contain any decimals or other separators. The amount has to be assigned dynamically.
        'currency', // Currency of the order in ISO alpha code. For instance: EUR, USD, GBP, …
        'language', // Language of the customer. For instance: en_US, nl_NL, fr_FR, …
        /*
        'Name', // field of the credit card details.
        */
        'CN', // Customer name. Will be pre-initialized (but still editable) in the Customer
        'EMAIL', // Customer’s e-mail address
        'owneraddress', // Customer’s street name and number
        'ownerZIP', // Customer’s ZIP code
        'ownertown', // Customer’s town/city name
        'ownercty', // Customer’s country
        'ownertelno', // Customer’s telephone number
        // return urls
        'accepturl',
        'declineurl',
        'exceptionurl',
        'cancelurl'
    );
    natcasesort($fields);
    //debug_printr($fields);
    
    return $fields;
}

function _ogone_sha_in_fields() {
    $uppercasefields = array('ACCEPTANCE', 'ACCEPTURL', 'ADDMATCH', 'ADDRMATCH', 'AIAGIATA', 'AIAIRNAME', 'AIAIRTAX', 'AIBOOKIND*XX*', 'AICARRIER*XX*', 'AICHDET', 'AICLASS*XX*', 'AICONJTI', 'AIDEPTCODE', 'AIDESTCITY*XX*', 'AIDESTCITYL*XX*', 'AIEXTRAPASNAME*XX*', 'AIEYCD', 'AIFLDATE*XX*', 'AIFLNUM*XX*', 'AIGLNUM', 'AIINVOICE', 'AIIRST', 'AIORCITY*XX*', 'AIORCITYL*XX*', 'AIPASNAME', 'AIPROJNUM', 'AISTOPOV*XX*', 'AITIDATE', 'AITINUM', 'AITINUML*XX*', 'AITYPCH', 'AIVATAMNT', 'AIVATAPPL', 'ALIAS', 'ALIASOPERATION', 'ALIASUSAGE', 'ALLOWCORRECTION', 'AMOUNT', 'AMOUNT*XX*', 'AMOUNTHTVA', 'AMOUNTTVA', 'BACKURL', 'BATCHID', 'BGCOLOR', 'BLVERNUM', 'BRAND', 'BRANDVISUAL', 'BUTTONBGCOLOR', 'BUTTONTXTCOLOR', 'CANCELURL', 'CARDNO', 'CATALOGURL', 'CAVV_3D', 'CAVVALGORITHM_3D', 'CERTID', 'CHECK_AAV', 'CIVILITY', 'CN', 'COM', 'COMPLUS', 'COSTCENTER', 'COSTCODE', 'CREDITCODE', 'CUID', 'CURRENCY', 'CVC', 'CVCFLAG', 'DATA', 'DATATYPE', 'DATEIN', 'DATEOUT', 'DECLINEURL', 'DEVICE', 'DISCOUNTRATE', 'DISPLAYMODE', 'ECI', 'ECI_3D', 'ECOM_BILLTO_POSTAL_CITY', 'ECOM_BILLTO_POSTAL_COUNTRYCODE', 'ECOM_BILLTO_POSTAL_NAME_FIRST', 'ECOM_BILLTO_POSTAL_NAME_LAST', 'ECOM_BILLTO_POSTAL_POSTALCODE', 'ECOM_BILLTO_POSTAL_STREET_LINE1', 'ECOM_BILLTO_POSTAL_STREET_LINE2', 'ECOM_BILLTO_POSTAL_STREET_NUMBER', 'ECOM_CONSUMERID', 'ECOM_CONSUMER_GENDER', 'ECOM_CONSUMEROGID', 'ECOM_CONSUMERORDERID', 'ECOM_CONSUMERUSERALIAS', 'ECOM_CONSUMERUSERPWD', 'ECOM_CONSUMERUSERID', 'ECOM_PAYMENT_CARD_EXPDATE_MONTH', 'ECOM_PAYMENT_CARD_EXPDATE_YEAR', 'ECOM_PAYMENT_CARD_NAME', 'ECOM_PAYMENT_CARD_VERIFICATION', 'ECOM_SHIPTO_COMPANY', 'ECOM_SHIPTO_DOB', 'ECOM_SHIPTO_ONLINE_EMAIL', 'ECOM_SHIPTO_POSTAL_CITY', 'ECOM_SHIPTO_POSTAL_COUNTRYCODE', 'ECOM_SHIPTO_POSTAL_NAME_FIRST', 'ECOM_SHIPTO_POSTAL_NAME_LAST', 'ECOM_SHIPTO_POSTAL_NAME_PREFIX', 'ECOM_SHIPTO_POSTAL_POSTALCODE', 'ECOM_SHIPTO_POSTAL_STREET_LINE1', 'ECOM_SHIPTO_POSTAL_STREET_LINE2', 'ECOM_SHIPTO_POSTAL_STREET_NUMBER', 'ECOM_SHIPTO_TELECOM_FAX_NUMBER', 'ECOM_SHIPTO_TELECOM_PHONE_NUMBER', 'ECOM_SHIPTO_TVA', 'ED', 'EMAIL', 'EXCEPTIONURL', 'EXCLPMLIST', 'EXECUTIONDATE*XX*', 'FACEXCL*XX*', 'FACTOTAL*XX*', 'FIRSTCALL', 'FLAG3D', 'FONTTYPE', 'FORCECODE1', 'FORCECODE2', 'FORCECODEHASH', 'FORCEPROCESS', 'FORCETP', 'GENERIC_BL', 'GIROPAY_ACCOUNT_NUMBER', 'GIROPAY_BLZ', 'GIROPAY_OWNER_NAME', 'GLOBORDERID', 'GUID', 'HDFONTTYPE', 'HDTBLBGCOLOR', 'HDTBLTXTCOLOR', 'HEIGHTFRAME', 'HOMEURL', 'HTTP_ACCEPT', 'HTTP_USER_AGENT', 'INCLUDE_BIN', 'INCLUDE_COUNTRIES', 'INVDATE', 'INVDISCOUNT', 'INVLEVEL', 'INVORDERID', 'ISSUERID', 'IST_MOBILE', 'ITEM_COUNT', 'ITEMATTRIBUTES*XX*', 'ITEMCATEGORY*XX*', 'ITEMCOMMENTS*XX*', 'ITEMDESC*XX*', 'ITEMDISCOUNT*XX*', 'ITEMID*XX*', 'ITEMNAME*XX*', 'ITEMPRICE*XX*', 'ITEMQUANT*XX*', 'ITEMUNITOFMEASURE*XX*', 'ITEMVAT*XX*', 'ITEMVATCODE*XX*', 'ITEMWEIGHT*XX*', 'LANGUAGE', 'LEVEL1AUTHCPC', 'LIDEXCL*XX*', 'LIMITCLIENTSCRIPTUSAGE', 'LINE_REF', 'LINE_REF1', 'LINE_REF2', 'LINE_REF3', 'LINE_REF4', 'LINE_REF5', 'LINE_REF6', 'LIST_BIN', 'LIST_COUNTRIES', 'LOGO', 'MAXITEMQUANT*XX*', 'MERCHANTID', 'MODE', 'MTIME', 'MVER', 'NETAMOUNT', 'OPERATION', 'ORDERID', 'ORDERSHIPCOST', 'ORDERSHIPTAX', 'ORDERSHIPTAXCODE', 'ORIG', 'OR_INVORDERID', 'OR_ORDERID', 'OWNERADDRESS', 'OWNERADDRESS2', 'OWNERCTY', 'OWNERTELNO', 'OWNERTOWN', 'OWNERZIP', 'PAIDAMOUNT', 'PARAMPLUS', 'PARAMVAR', 'PAYID', 'PAYMETHOD', 'PM', 'PMLIST', 'PMLISTPMLISTTYPE', 'PMLISTTYPE', 'PMLISTTYPEPMLIST', 'PMTYPE', 'POPUP', 'POST', 'PSPID', 'PSWD', 'REF', 'REFER', 'REFID', 'REFKIND', 'REF_CUSTOMERID', 'REF_CUSTOMERREF', 'REGISTRED', 'REMOTE_ADDR', 'REQGENFIELDS', 'RTIMEOUT', 'RTIMEOUTREQUESTEDTIMEOUT', 'SCORINGCLIENT', 'SETT_BATCH', 'SID', 'STATUS_3D', 'SUBSCRIPTION_ID', 'SUB_AM', 'SUB_AMOUNT', 'SUB_COM', 'SUB_COMMENT', 'SUB_CUR', 'SUB_ENDDATE', 'SUB_ORDERID', 'SUB_PERIOD_MOMENT', 'SUB_PERIOD_MOMENT_M', 'SUB_PERIOD_MOMENT_WW', 'SUB_PERIOD_NUMBER', 'SUB_PERIOD_NUMBER_D', 'SUB_PERIOD_NUMBER_M', 'SUB_PERIOD_NUMBER_WW', 'SUB_PERIOD_UNIT', 'SUB_STARTDATE', 'SUB_STATUS', 'TAAL', 'TAXINCLUDED*XX*', 'TBLBGCOLOR', 'TBLTXTCOLOR', 'TID', 'TITLE', 'TOTALAMOUNT', 'TP', 'TRACK2', 'TXTBADDR2', 'TXTCOLOR', 'TXTOKEN', 'TXTOKENTXTOKENPAYPAL', 'TYPE_COUNTRY', 'UCAF_AUTHENTICATION_DATA', 'UCAF_PAYMENT_CARD_CVC2', 'UCAF_PAYMENT_CARD_EXPDATE_MONTH', 'UCAF_PAYMENT_CARD_EXPDATE_YEAR', 'UCAF_PAYMENT_CARD_NUMBER', 'USERID', 'USERTYPE', 'VERSION', 'WBTU_MSISDN', 'WBTU_ORDERID', 'WEIGHTUNIT', 'WIN3DS', 'WITHROOT');
    
    natcasesort($uppercasefields);
    return $uppercasefields;
}
function _ogone_sha_out_fields() {
    $uppercasefields = array('AAVADDRESS', 'AAVCHECK', 'AAVZIP', 'ACCEPTANCE', 'ALIAS', 'AMOUNT', 'BIN', 'BRAND', 'CARDNO', 'CCCTY', 'CN', 'COMPLUS', 'CREATION_STATUS', 'CURRENCY', 'CVCCHECK', 'DCC_COMMPERCENTAGE', 'DCC_CONVAMOUNT', 'DCC_CONVCCY', 'DCC_EXCHRATE', 'DCC_EXCHRATESOURCE', 'DCC_EXCHRATETS', 'DCC_INDICATOR', 'DCC_MARGINPERCENTAGE', 'DCC_VALIDHOURS', 'DIGESTCARDNO', 'ECI', 'ED', 'ENCCARDNO', 'IP', 'IPCTY', 'NBREMAILUSAGE', 'NBRIPUSAGE', 'NBRIPUSAGE_ALLTX', 'NBRUSAGE', 'NCERROR', 'ORDERID', 'PAYID', 'PM', 'SCO_CATEGORY', 'SCORING', 'STATUS', 'SUBBRAND', 'SUBSCRIPTION_ID', 'TRXDATE', 'VC');
    
    natcasesort($uppercasefields);
    return $uppercasefields;
}

/**
 * Status codes for ogone responses
 *
	Status of the payment.
	The table above summarises the possible statuses of the payments.
	
	Statuses in 1 digit are 'normal' statuses:
	0 means the payment is invalid (e.g. data validation error) or the processing is not complete either because it is still underway, or because the transaction was interrupted. If the cause is a validation error, an additional error code (*) (NCERROR) identifies the error.
	1 means the customer cancelled the transaction.
	2 means the acquirer did not authorise the payment.
	5 means the acquirer autorised the payment.
	9 means the payment was captured.
	
	Statuses in 2 digits correspond either to 'intermediary' situations or to abnormal events. When the second digit is:
	1, this means the payment processing is on hold.
	2, this means an unrecoverable error occurred during the communication with the acquirer. The result is therefore not determined. You must therefore call the acquirer's helpdesk to find out the actual result of this transaction.
	3, this means the payment processing (capture or cancellation) was refused by the acquirer whilst the payment had been authorised beforehand. It can be due to a technical error or to the expiration of the authorisation. You must therefore call the acquirer's helpdesk to find out the actual result of this transaction.
	4, this means our system has been notified the transaction was rejected well after the transaction was sent to your acquirer.
	5, this means our system hasn’t sent the requested transaction to the acquirer since the merchant will send the transaction to the acquirer himself, like he specified in his configuration.
 **/
function _ogone_status_codes($lookup=false) {

	$returncodes = array(
		'0'=>'Incomplete or invalid',
		'1'=>'Cancelled by client',
		'2'=>'Authorization refused',
		
		'4'=>'Order stored',
		'41'=>'Waiting client payment',
		
		'5'=>'Authorized',
		'51'=>'Authorization waiting',
		'52'=>'Authorization not known',
		'59'=>'Author. to get manually',
		
		'6'=>'Authorized and canceled',
		'61'=>'Author. deletion waiting',
		'62'=>'Author. deletion uncertain',
		'63'=>'Author. deletion refused',
		
		'7'=>'Payment deleted',
		'71'=>'Payment deletion pending',
		'72'=>'Payment deletion uncertain',
		'73'=>'Payment deletion refused',
		'74'=>'Payment deleted (not accepted)',
		'75'=>'Deletion processed by merchant',
		
		'8'=>'Refund',
		'81'=>'Refund pending',
		'82'=>'Refund uncertain',
		'83'=>'Refund refused',
		'84'=>'Payment declined by the acquirer (will be debited)',
		'85'=>'Refund processed by merchant',
		
		'9'=>'Payment requested',
		'91'=>'Payment processing',
		'92'=>'Payment uncertain',
		'93'=>'Payment refused',
		'94'=>'Refund declined by the acquirer',
		'95'=>'Payment processed by merchant',
		'97'=>'Being processed (intermediate technical status)',
		'98'=>'Being processed (intermediate technical status)',
		'99'=>'Being processed (intermediate technical status)'
	);
	if($lookup===false) {
		return $returncodes;
	} else {
		if(isset($returncodes[$lookup])) {
			return $returncodes[$lookup];
		} else {
			return $returncodes[0];
		}
	}
}

function _ogone_urls($provider, $testmode=true) {
    if($testmode) {
        $infix = 'test';
    } else {
        $infix = 'prod';
    }
    
    switch($provider) {
        case 'rabobank':
            $url = 'https://i-kassa.rabobank.nl/rik/'.$infix.'/orderstandard.asp';
            break;
        case 'abnamro':
            $url = 'https://internetkassa.abnamro.nl/ncol/'.$infix.'/orderstandard.asp';
            break;
        case 'ogone':
        default:
            $url = 'https://secure.ogone.com/ncol/'.$infix.'/orderstandard.asp';
            break;
    }
    
    return $url;
}

/**
 * Add the return hook
 */
$this->addHook(
    '_ogone_report_page',
    'callback',
    '_ogone_report_page'
    );

/**
 * background page where iDEAL via ogone.nl payments reports transaction status
 */
function _ogone_report_page($orderandparams) {
    global $PIVOTX;
    
    $order = $orderandparams['order'];
    $params = $orderandparams['params'];
    
    $ordertotals = $order->getOrderTotals();

    $order_id = $order->getOrderId();
    $order_details = $order->getOrderDetails();
    
	debug('ogone report order starting transaction: '.$order_id);
    if(0 && $PIVOTX['config']->get('shop_ogone_testmode')) {
		debug('ogone report order orderandparams');
		debug_printr($orderandparams);
		debug('ogone report order ordertotals');
		debug_printr($ordertotals);
		debug('ogone report order order_details');
		debug_printr($order_details);
    }
	
    // check SHASIGN
    $shop_config['shop_ogone_sha1out'] = $PIVOTX['config']->get('shop_ogone_sha1out', 'test');
    $shop_config['shop_ogone_encryption'] = $PIVOTX['config']->get('shop_ogone_encryption', 'sha1');

    $shaoutkeys = _ogone_sha_out_fields();
   
    $paramkeys = array_keys($params); 
    natcasesort($paramkeys);  

    $shastring = '';
    $shavalues = '';
    foreach($paramkeys as $key) {
        if($key == 'status') { // lowercase status
            //nothing
            //debug('dont use get status');
        } elseif(in_array(strtoupper($key), $shaoutkeys) && ($params[$key]!='')) {
            $shastring .= strtoupper($key).'='.$params[$key].$shop_config['shop_ogone_sha1out'];
            $shavalues .= strtoupper($key).'='.$params[$key]." \n ";
        }
    }
    if($shop_config['shop_ogone_encryption'] == 'sha512') {
        $shaencoded = strtoupper(hash('sha512', $shastring));
    } else {
        $shaencoded = strtoupper(sha1($shastring));
    }
    if(0 && $PIVOTX['config']->get('shop_ogone_testmode')) {
		debug('ogone report order sha values');
        debug('shasecret : '.$shop_config['shop_ogone_sha1out']);
        debug('enctype : '.$shop_config['shop_ogone_encryption']);
        debug('shastring : '."\n".$shastring);
        debug('shavalues : '.$shavalues);
        debug('SHASIGN___ : '.$params['SHASIGN']);
        debug('shaencoded : '.$shaencoded);
    }
    
    if($shaencoded == $params['SHASIGN']) {
        if($PIVOTX['config']->get('shop_ogone_testmode')) {
            debug('ogone report page shasign = valid');
            debug('ogone report page request variables');
			debug_printr($_REQUEST);
            debug('ogone report page server variables');
			debug_printr($_SERVER);
		}
        if(round($order_details['totals']['cumulative_incl_tax']) != round($params['amount']*100)) {
            if($PIVOTX['config']->get('shop_ogone_testmode')) {
				debug('amount: '.$params['amount'] .' ==? order incl tax: '.$order_details['totals']['cumulative_incl_tax']);
				debug('ogone report order order amount not valid!');
            }
            $order_details['order_status'] = 'error';
            $order_details['payment_datetime'] = date("Y-m-d H:i:s", time());
            $order_details['payment_message'] = $order_details['payment_message'] . "\n". $params['STATUS'] ." - " .  $returncodes[$params['STATUS']] . ': amount code does not match ('.($params['amount']*100).' should be '.$order_details['totals']['cumulative_incl_tax'].')';
            $order_details['payment_status'] = 'error';
			$order_details['payment_amount'] = $params['amount']*100;
			
            $order_details = _shop_save_order($order_details);
            print 'error';
            if($PIVOTX['config']->get('shop_ogone_testmode')) {
                debug('ogone report order abort transaction with error: '.$order_id);
            }
            exit;
        }
        $returncodes =  _ogone_status_codes();
        $order_details['payment_status'] = $params['STATUS'] . ': ' . $returncodes[$params['STATUS']];
        $order_details['payment_external_code'] = $params['PAYID'];
		$order_details['order_old_status'] = $order_details['order_status'];
		$order_details['payment_old_status']= $order_details['payment_status'];
		
        switch($params['STATUS']) {
            case 9:
                $order_details['order_status'] = 'complete';
                break;
            case 0:
                $order_details['order_status'] = 'error';
                break;
            case 1:
                $order_details['order_status'] = 'cancelled';
                break;
            default:
                $order_details['order_status'] = 'waiting';
                break;
        }
		
		
        $order_details['payment_datetime'] = date("Y-m-d H:i:s", time());
        $order_details['payment_amount_total'] = ($params['amount']*100);
        $order_details['payment_message'] = $order_details['payment_message'] . "\n". $params['STATUS'] ." - " .  $returncodes[$params['STATUS']];
		if($order_details['order_old_status']=='waiting' && $order_details['order_status'] == 'complete') {
			if(in_array($order_details['payment_old_status'], array(
												'91: Payment processing',
												'92: Payment uncertain',
												'95: Payment processed by merchant'))) {
				if($PIVOTX['config']->get('shop_ogone_testmode')) {
					debug('order ogone report - sending mail using template: '.$order_details['payment_provider'].'_return_tpl');
				}
				$order_details['payment_message'] = $order_details['payment_message'] . "\nSent confirmation email again with completed payment message";
				// order was upgraded - send an email
				$order_details = _shop_order_mail_default($order_details['payment_provider'].'_return_tpl', $order_details);
			}
		}

        $order_details = _shop_save_order($order_details);
    } else {
        if($PIVOTX['config']->get('shop_ogone_testmode')) {
            debug('ogone report order shasign not valid!');
        }
        $order_details['order_status'] = 'error';

        $order_details['payment_datetime'] = date("Y-m-d H:i:s", time());
        $order_details['payment_message'] = $order_details['payment_message'] . "\n". $params['STATUS'] ." - " .  $returncodes[$params['STATUS']] . ': SHASIGN code does not match';
        $order_details['payment_status'] = 'error';
        $order_details = _shop_save_order($order_details);

    }
    // print minimal output as required by the api
    print 'OK';
    if($PIVOTX['config']->get('shop_ogone_testmode')) {
        debug('ogone report order complete transaction: '.$order_id);
    }
    exit;
}



/**
 * Add the return hook
 */
$this->addHook(
    '_ogone_return_page',
    'callback',
    '_ogone_return_page'
    );

/**
 * return page where ogone sends customers after payment
 */
function _ogone_return_page($orderandparams) {
    global $PIVOTX;
    
    //debug('ogone report orderandparams');
    //debug_printr($orderandparams);
    
    $order = $orderandparams['order'];
    $params = $orderandparams['params'];
    //debug_printr($orderparms);
    //$order = $PIVOTX['order'];
    //debug_printr($order);
    $ordertotals = $order->getOrderTotals();
    //debug_printr($ordertotals);
    $order_id = $order->getOrderId();
    $order_details = $order->getOrderDetails();

    if(0 && $PIVOTX['config']->get('shop_ogone_testmode')) {
		debug('ogone return page orderandparams');
		debug_printr($orderandparams);
		debug('ogone return page ordertotals');
		debug_printr($ordertotals);
		debug('ogone return page order_details');
		debug_printr($order_details);
	    debug('ogone return page starting transaction return: '.$order_id);
    }

    // check SHASIGN
    $shop_config['shop_ogone_sha1out'] = $PIVOTX['config']->get('shop_ogone_sha1out', 'test');
    $shop_config['shop_ogone_encryption'] = $PIVOTX['config']->get('shop_ogone_encryption', 'sha1');

    $shaoutkeys = _ogone_sha_out_fields();
   
    $paramkeys = array_keys($params); 
    natcasesort($paramkeys);  

    $shastring = '';
    $shavalues = '';
    foreach($paramkeys as $key) {
        if($key == 'status') { // lowercase status
            //nothing
            //debug('dont use get status');
        } elseif(in_array(strtoupper($key), $shaoutkeys) && ($params[$key]!='')) {
            $shastring .= strtoupper($key).'='.$params[$key].$shop_config['shop_ogone_sha1out'];
            $shavalues .= strtoupper($key).'='.$params[$key]." \n ";
        }
    }
    if($shop_config['shop_ogone_encryption'] == 'sha512') {
        $shaencoded = strtoupper(hash('sha512', $shastring));
    } else {
        $shaencoded = strtoupper(sha1($shastring));
    }
    if(0 && $PIVOTX['config']->get('shop_ogone_testmode')) {
		debug('ogone return page sha out');
        debug('shasecret : '.$shop_config['shop_ogone_sha1out']);
        debug('enctype : '.$shop_config['shop_ogone_encryption']);
        debug('shastring : '."\n".$shastring);
        debug('shavalues : '."\n".$shavalues);
        debug('SHASIGN___ : '.$params['SHASIGN']);
        debug('shaencoded : '.$shaencoded);
    }

    if($shaencoded != $params['SHASIGN']) {
        debug('ogone return page: sha error');
        $params['title'] = st('Error');
        $params['body'] = '<p>'. st('No order found.') . '</p>';
        $params['body'] .= '<p><a href="'.$return_url.'" class="continue_shopping">'. st('Continue shopping') .'</a></p>';
        $params['action'] = 'return';
        return $params;
    } elseif ($order_details['order_public_code']) {
        /*
          Via report.php heeft Ogone de betaling al gemeld, en in dat script heeft bij Ogone gecontroleerd 
          wat de betaalstatus is. Deze betaalstatus is in report.php ergens opgeslagen in het systeem (bijv. 
          in de database).
         
          De klant komt bij dit script terug na de betaling.
        */
        if($order_details['order_status']=='complete') {
            $title = st('Thanks');
            $output = st('Thank you for your order.') . '<br />'
                      .st('Your payment is confirmed.') . '<br />'
                      .st('You will get a message with the confirmation soon.');
			$mail_on_complete = true;
        } elseif($order_details['order_status']=='waiting') {
			// status not complete
            $title = st('Thanks');
            $output = st('Thank you for your order.') . '<br />'
                      . st('Your payment is not yet confirmed.') . '<br />'
                      . st('As soon as your payment is confirmed you will receive a message.');
			$mail_on_complete = true;
        } elseif($order_details['order_status']=='cancelled') {
			// status not complete
            $title = st('Cancelled');
            $output = st('Your order is cancelled.') . '<br />'
                      . st('Either you cancelled the payment or something went wrong during the payment process.') . '<br />'
                      . st('If you still want to order, you will have to order again.') . '<br />'
                      . st('You will not receive a confirmation message.');
			$mail_on_complete = false;
        } else {
            $title = st('Error');
            $output = st('Your order is cancelled.') . '<br />'
                      . st('Something went wrong during the payment process.') . '<br />'
                      . st('If you still want to order, you will have to order again.') . '<br />'
                      . st('You will not receive a confirmation message.');
			$mail_on_complete = false;
        }

        $params['title'] = $title;
        if($order_details && $mail_on_complete) {
			// only show order summary when successfull
            $params['body'] = '<p>'. $output .'</p>';
            $params['body'] .= _shop_order_summary($order_details);
        } else {
            $params['body'] = '<p>'. $output .'</p>';
        }

		if(0 && $PIVOTX['config']->get('shop_ogone_testmode')) {
			debug('order ogone returned - sending mail using template: '.$order_details['payment_provider'].'_return_tpl');
			debug('ogone return page params');
			debug_printr($params);
			debug('ogone return page order details');
			debug_printr($order_details);
		}
		if($mail_on_complete) {
	        // drop a mail with instructions
			$order_details = _shop_order_mail_default($order_details['payment_provider'].'_return_tpl', $order_details);
		}
		
        $return_url = $PIVOTX['config']->get('shop_default_homepage', '/index.php?w=shop');
        $params['body'] .= '<p><a href="'.$return_url.'" class="continue_shopping">'. st('Continue shopping') .'</a></p>';
    } else {
		debug('ogone return page:'. st('No order found.'));
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
    '_ogone_payment_info',
    'callback',
    '_ogone_payment_info'
    );

function _ogone_payment_info($label) {
	global $PIVOTX;
	$shop_ogone_provider = $PIVOTX['config']->get('shop_ogone_provider','ogone');
	
	if ($label=='ogone' && $shop_ogone_provider == 'ogone') {
        return '<span class="ideal_label">iDEAL</span> via ogone.nl';
    } elseif ($label=='ogone' && $shop_ogone_provider == 'rabobank') {
        return '<span class="ideal_label">iDEAL</span> via <span class="rabobank_label">Rabobank</span>';
    } elseif ($label=='ogone' && $shop_ogone_provider == 'abnamro') {
        return '<span class="ideal_label">iDEAL</span> via <span class="abnamro_label">ABN-Amro</span>';
    }
    return $label;
}

/**
 * Add the paymentinfo hook
 */
$this->addHook(
    '_ogone_payment_status',
    'callback',
    '_ogone_payment_status'
    );

/**
 * Todo: call this one on return pages too
 */
function _ogone_payment_status($order) {
	global $PIVOTX;
	debug('_ogone_payment_status order=: '. print_r($order, true));
	debug('_ogone_payment_status called with verbose=: '. print_r($verbose, true));
	
	if($order['payment_provider'] == 'ogone') {
	   if(in_array($order['payment_status'], array('9: Payment requested'))) {
			// this is the completed payment
			if($order['verbose_output']) { $output = '<h4>'. st('Payment successful') .'</h4>'; }
			$output .= '<p>'. st('Payment handled by') .': '.$paymentprovider.'</p>';
		} elseif(in_array($order['payment_status'], array(
												'91: Payment processing',
												'92: Payment uncertain',
												'95: Payment processed by merchant'))) {
			// this is a pending payment
			if($order['verbose_output']) { $output = '<h4>'. st('Payment waiting') .'</h4>'; }
			$output = '<p>'. st('Payment waiting') .'</p>';
			$output .= '<p>'. st('You will receive a message when the status changes.') .'</p>';
		} elseif(in_array($order['payment_status'], array('1: Cancelled by client'))) {
			// no we wont do anything here
			if($order['verbose_output']) { $output = '<h4>'. st('Cancelled') .'</h4>'; }
			$output = '<p>'. st('Your order is cancelled.') .'</p>';
			$output .= '<p>'. st('You will not receive a confirmation message.') .'</p>';
		} else {
			// maybe something will happen
			if($order['verbose_output']) { $output = '<h4>'. st('Payment incomplete') .'</h4>'; }
			$output .= '<p>'. st('You will receive a message with further instructions for payment.') .'</p>';
		}
	} else {
		// kaput
		if($order['verbose_output']) { $output = '<h4>'. st('Error') .'</h4>'; }
		$output .= '<p>'. st('Something went wrong during the payment process.') .'</p>';
		$output .= '<p>'. st('You will not receive a confirmation message.') .'</p>';
	}
    return $output;
}