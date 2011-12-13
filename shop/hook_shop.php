<?php
// - Extension: Shop
// - Version: 0.2-40
// - Author: Two Kings // Lodewijk Evers
// - E-mail: lodewijk@twokings.nl
// - Description: A plug and play shop extension (needs pivotx 3 or higher)
// - Date: 2011-12-13
// - Identifier: shop
// - Required PivotX version: 3

/**
 * FIXME: add registry thingy for mailer templates for global killswitch
 */

DEFINE('SHOP_VERSION', 40);
$shopbasedir = dirname(__FILE__);

/**
 * autoload all modules
 */
if(!class_exists('ShopCart')) {
    foreach (glob($shopbasedir."/modules/shop_*.php") as $filename) {
        include($filename);
    }
    if (defined('PIVOTX_INWEBLOG')) {
        $PIVOTX['shoppingcart'] = new ShopCart();
    }
}

if($PIVOTX['config']->get('shop_enabled')!=false) {
    /* Add a hook to global PivotX init
     *
     * Take over actions related to the buythisbutton
     **/
    $this->addHook(
        'after_initialize',
        'callback',
        '_shop_initialize_callback'
        );
}

function _shop_initialize_callback() {
    // check if the requested action is not handled by this extension
    $actionoptions = array(
        'buy',
        'add',
        'update',
        'refresh',
        'remove',
        'cart',
        'checkout',
        'payment',
        'return',
        'success',
        'failure',
        'cancel',
        'editextra',
        'delextra',
        // --- overly verbose
        'addtocart',
        'updatecart',
        'removefromcart',
        'gocheckout',
        'gopayment',
        'returnfrompaymentsuccess',
        'returnfrompaymentfailure',
    );
    $backendoptions = array(
        'verify',
        'report',
        // --- overly verbose
        'verifycheckout',
        'verifypayment',
    );
    if(in_array($_REQUEST['action'], $backendoptions)) {
        // validate keys
        $params = cleanParams($_REQUEST);        
        // if keys validate show page
        switch($params['action']) {
            case 'verifycheckout':
            case 'verify':
                shop_verify_page($params);
                break;
            case 'verifypayment':
            case 'report':
                shop_report_page($params);
                break;
            default:
                shop_error_page();
        }
        
        // we've done the action, now get lost!
        exit;
    }
    if(in_array($_REQUEST['action'], $actionoptions)) {

        // validate keys
        $params = cleanParams($_REQUEST);
        
        // if keys validate show page
        switch($params['action']) {
            case 'addtocart':
            case 'buy':
            case 'add':
                shop_cart_add_page($params);
                break;
            case 'updatecart':
            case 'update':
            case 'refresh':
                shop_cart_update_page($params);
                break;
            case 'cart':
                shop_cart_page($params);
                break;
            case 'removefromcart':
            case 'remove':
                shop_cart_remove_page($params);
                break;
            case 'gocheckout':
            case 'checkout':
                shop_checkout_page($params);
                break;
            case 'gopayment':
            case 'payment':
                shop_payment_page($params);
                break;
            case 'returnfrompaymentsuccess':
            case 'return':
            case 'success':
                shop_return_page($params);
                break;
            case 'returnfrompaymentfailure':
            case 'failure':
                shop_failure_page($params);
                break;
            case 'cancel':
                shop_cancel_page($params);
                break;

            case 'editextra':
            case 'delextra':
                shop_extra_page($params);
                break;

            default:
                shop_error_page();
        }
        
        // we've done the action, now get lost!
        exit;
    }
    // do nothing and continue when the requested action is not handled by this extension
}
