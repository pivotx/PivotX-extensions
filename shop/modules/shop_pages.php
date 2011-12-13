<?php

/**
 * PAgeh andler for adding an item to the cart
 */
function shop_cart_add_page($params) {
    global $PIVOTX;
    
    if($params['item_no_items']>0) {
        // load the entry if it's only an id
        if(isset($params['entry']) && !is_array($params['entry'])) {
            $params['entry'] = $PIVOTX['db']->read_entry($params['entry']);
        }
        $no_items = round($params['item_no_items']);
		$option = isset($params['item_product_options'])?trim($params['item_product_options']):false;
        $cart = _shop_add_to_cart($params['entry'], $no_items, $option);

		if($params['fromajax']=='yes') {
			$cart = '<div class="cartaddmessage positive">'.st('The item has been added to your cart').'</div>'."\n";
			$cart .= _shop_show_cart('compact');
			
			print $cart;
 			exit;
		} else {
			// redirect to cart page
			// makes the annoying form refresh go away
			// return shop_cart_page($params);
			header("Location: /index.php?action=cart");
			exit;
		}
    }
    shop_render_page($params);
}
function shop_cart_update_page($params) {
    //debug('update cart');
    //debug_printr($params);
    if($params['item_no_items']>0 && isset($params['entry']) && !is_array($params['entry'])) {
        $no_items = round($params['item_no_items']);
		$option = !empty($params['item_product_options'])?trim($params['item_product_options']):false;

		$cart = _shop_update_cart($params['entry'], $no_items, $option);
        // redirect to cart page
        // makes the annoying form refresh go away
        // return shop_cart_page($params);
        header("Location: /index.php?action=cart");
        exit;
    }
    shop_render_page($params);
}
function shop_cart_remove_page($params) {
	//debug_printr($params);
	//debug('trying to delete item');
    if(isset($params['entry']) && !is_array($params['entry'])) {
		$option = !empty($params['item_option'])?trim($params['item_option']):false;
		
        $cart = _shop_remove_from_cart($params['entry'], $option);
        // redirect to cart page
        // makes the annoying form refresh go away
        //return shop_cart_page($params);
        header("Location: /index.php?action=cart");
        exit;
    }
    shop_render_page($params);
}
function shop_cart_page($params) {
    // simply show the cart
    $params['title'] = st('Shopping cart');
    $params['body'] = _shop_show_cart('full', true);
    shop_render_page($params);
}
function shop_checkout_page($params) {
	if(!_shop_check_cart()) {
        // redirect to cart page
        //return shop_cart_page($params);
        header("Location: /index.php?action=cart");
        exit;
    }
    if($params['checkout_submit']==st('Continue')) {
        $params = _shop_validate_checkoutform($params);
    }
    if($params['checkout_submit']==st('Continue') && $params['errors']==0) {
        // save userdata and create order
        //debug('params before first order save:');
        //debug_printr($params);
        $order = _shop_save_order($params);
        //debug('order after first order save:');
        //debug_printr($order);
        // redirect to payment page
        //return shop_payment_page($params);
        header("Location: /index.php?action=payment");
        exit;
    }
    // not submitted anything
    $params['title'] = st('Checkout');
    $params['body'] = _shop_show_checkoutform($params);

    shop_render_page($params);
}
function shop_payment_page($params) {
    global $PIVOTX;
	
	//debug('payment page params:');
    //debug_printr($params);
	
	$order_id = $PIVOTX['session']->getValue('order_id');
	$order_public_code = $PIVOTX['session']->getValue('order_public_code');
	$order_public_hash = $PIVOTX['session']->getValue('order_public_hash');
    
    $orderparms = array(
        'order_id' => $order_id,
        'order_public_code' => $order_public_code,
        'order_public_hash' => $order_public_hash,
    );
	
	
    //_shop_show_order($size = 'full', $orderparms=array());
	$order = _shop_load_order($orderparms);
	$payment_provider = $order->getPaymentProvider();
	$orderparms = array_merge($orderparms, $params);
	
    //debug('order params:');
    //debug_printr($orderparms);
	
    //debug('payment provider: '.$payment_provider);
    if($payment_provider!='other') {
		$hook = _shop_load_hook('prepare', $payment_provider);

		//debug('hook: '. $hook);

		// the option to override it all with extensions
		// we also have the global $PIVOTX['order']
		$page = $PIVOTX['extensions']->executeHook($hook, $orderparms);
		
		$page = array_merge($page, $params);
		//debug('page to render:');
		//debug_printr($page);
        shop_render_page($page);
        exit;
    } else {
        // do default stuff
		$order_details = $order->getOrderDetails();
		
        $order_details['order_status'] = 'waiting';
        $order_details['payment_provider'] == 'other';
        // save userdata and create order
        $order_details = _shop_save_order($order_details);

        // drop a mail with instructions
        $order_details = _shop_order_mail_default($order_details['payment_provider'].'_return_tpl', $order_details);

        // redirect to return page
        header("Location: /index.php?action=return");
        exit;
    }
    
    // catastrophic failure
    $params['title'] = 'Payment failure';
    $params['body'] = '<p>Something really went bad, please check the debug log for info</p>';
    debug_printr($order);
    //$params['body'] = '<pre>'.print_r($order, true).'</pre>';
    shop_render_page($params);
}
/**
 * Return page for orders after payment or on other completion of order process
 */
function shop_return_page($params) {
    global $PIVOTX;

	//debug('return page params:');
    //debug_printr($params);
	
	$PIVOTX['extensions']->executeHook('_shop_payment_return_variables', $params);

    //debug('return page order params:');
    //debug_printr($params);
    
	$order = _shop_load_order($params);
	$payment_provider = $order->getPaymentProvider();
	//debug('report page params:');
    //debug_printr($params);
	
	//debug('payment provider: '.$payment_provider);
    if($params['transaction_id'] && $payment_provider!='other') {
		//debug('normal return page hook');
		$hook = _shop_load_hook('return', $payment_provider);
		$orderandparams = array('order' => $order, 'params'=> $params);
		//debug('hook: ' . $hook);
		//debug_printr($orderandparams);
		// the option to override it all with extensions
		$page = $PIVOTX['extensions']->executeHook($hook, $orderandparams);
        shop_render_page($page);
        exit;
    } else {
		$order_details = $order->getOrderDetails();
		
        if($order_details) {
            $title = st('Thanks');
            $output = st('Your order is received.') . st('You will receive a message with further instructions for payment.');
			
			// geannuleerde orders komen hier ook uit
			debug('order default returned - sending mail using template: '.$order_details['payment_provider'].'_return_tpl');
			debug_printr($order_details);
			$order_details = _shop_order_mail_default($order_details['payment_provider'].'_return_tpl', $order_details);
			
        } else {
            $title = st('Error');
            $output = st('No order found.');
			$output .= '<pre>'.printr_r($params, true).'</pre>';
        }
    }
    $params['title'] = $title;
    if($order) {
        $params['body'] = $output;
        $params['body'] .= _shop_order_summary($order_details);
    } else {
        $params['body'] = $output;
    }
    $return_url = $PIVOTX['config']->get('shop_default_homepage', '/index.php?w=shop');
    $params['body'] .= '<p><a href="'.$return_url.'" class="continue_shopping">'. st('Continue shopping') .'</a></p>';
	
    shop_render_page($params);
}
/**
 * Show report page
 */
function shop_report_page($params) {
    global $PIVOTX;

	//debug('report page params:');
    //debug_printr($params);
	
	$PIVOTX['extensions']->executeHook('_shop_payment_report_variables', $params);

    //debug('report page order params:');
    //debug_printr($params);
    
	$order = _shop_load_order($params);
	$payment_provider = $order->getPaymentProvider();
	//debug('report page params:');
    //debug_printr($params);
	
	//debug('payment provider: '.$payment_provider);
    if($params['transaction_id'] && $payment_provider!='other') {
		//debug('normal report page hook');
		$hook = _shop_load_hook('report', $payment_provider);
		$orderandparams = array('order' => $order, 'params'=> $params);
		//debug('hook: ' . $hook);
		//debug_printr($orderandparams);
		// the option to override it all with extensions
		$page = $PIVOTX['extensions']->executeHook($hook, $orderandparams);
        shop_render_page($page);
        exit;
    } else {
		debug('report page called for unknown method');
        header("HTTP/1.0 404 Not Found");
        print "Report method unknown";
        exit;
    }
}

/**
 * The output function that does all the rendering
 */
function shop_render_page($params) {
    global $PIVOTX, $shop_cart_config;

    //debug_printr($params);

    $themename = getDefault($PIVOTX['config']->get('shop_default_theme'), 'skinny');
    $template = getDefault($PIVOTX['config']->get('shop_default_template'), 'shop.html');
    
    // override fallback template with default
	//debug('attempting to load template: '.$PIVOTX['paths']['templates_path'].$themename.'/'.$template);
    if(file_exists($PIVOTX['paths']['templates_path'].$themename.'/'.$template)) {
        $template = $themename.'/'.$template;
    } else {
        $template = $themename.'/page_template.html';
        debug('template failure - fallback to '.$template);
    }
    
    $PIVOTX['template']->assign('page', $params);
    // show the cart page as a default
    if(empty($params['body']) && empty($params['title'])) {
        // Set the page in $smarty as an array, as well as separate variables.
        $PIVOTX['template']->assign('title', st('Shopping cart'));
        $PIVOTX['template']->assign('body', _shop_show_cart());
    } else {
        $PIVOTX['template']->assign('title', $params['title']);
        $PIVOTX['template']->assign('body', $params['body']);
    }

    if(is_array($page)) {
        foreach($page as $key=>$value) {
            $PIVOTX['template']->assign($key, $value);
        }
    }
    if(is_array($page['hooks'])) {
        foreach($page['hooks'] as $key=>$value) {
            $PIVOTX['template']->assign($key, $value);
        }
    }
	
	if(!$shop_cart_config['js_inserted']) {
		// we always want to see jquery
		$PIVOTX['extensions']->addHook('after_parse', 'callback', 'jqueryIncludeCallback');
		
		$js_head = str_replace('[[extensionurl]]', $PIVOTX['paths']['extensions_url'], '<script type="text/javascript" src="[[extensionurl]]shop/js/pivotx.cart.js"></script>');
		$PIVOTX['extensions']->addHook('after_parse', 'insert_before_close_head', $js_head);
		$shop_cart_config['js_inserted'] = true;
	}

    $parser = new Parser();
    $parser->modifier['template'] = $template;
    $parser->modifier['themename'] = $themename;
    $parser->modifier['home'] = false;
    $parser->modifier['weblog'] = false;
    $parser->modifier['shop'] = true;
    $parser->modifier['shop_action'] = $params['action'];
    $parser->modifier['uid'] = null;
    $parser->modifier['pagetype'] = 'custom';
    $parser->modifier['page'] = true;
    $parser->renderCustom();
    $parser->output();
    
    //shop_debug($params['action'], $params);

}

/**
 * Extra options
 */
function shop_extra_page($params)
{
    global $PIVOTX;

    $entry = false;
    if(isset($params['entry']) && !is_array($params['entry'])) {
        $entry = $PIVOTX['db']->read_entry($params['entry']);
    }

    if (is_array($entry)) {
        $PIVOTX['shoppingcart']->deleteItem($entry['uid']);
        if (isset($params['option'])) {
            $PIVOTX['shoppingcart']->addItem($entry['uid'],1,$params['option']);
        }
    }

    header('location: /index.php?action=cart');
    exit();
}

/**
 * The output function that does debug output for development
 */
function shop_debug($action=false, $params=false) {
    $outparms = array(
                  '$_REQUEST'=>$_REQUEST,
                  '$_COOKIE'=>$_COOKIE,
                  '$_SESSION'=>$_SESSION,
                  '$_GET'=>$_GET,
                  '$_POST'=>$_POST);
    
    if(!is_array($action)) {
        if($action==false) {
            debug("What kind of action did you expect to do here?");
        } else {
            debug('Sweet '. safeString($action)." action going on!");
        }
    } else {
        $outparms['$action'] = $action;
    }
    if($params) {
        $outparms['$params'] = $params;
    }
    debug(print_r($outparms, true));
    //debug('shop_debug disabled');
}
