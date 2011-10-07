<?php
/**
 * Most of the functions for the store are here
 */

if($PIVOTX['config']->get('shop_enabled')!=false) {
	/**
	 * Add a hook to insert the buy this link after the body of an entry
	 */
	$this->addHook(
		'entry_afterload',
		'callback',
		'_shop_afterload_parse'
		);
	
	/**
	 * Add a hook to insert the css for the shop in the head
	 */
	$this->addHook(
		'after_initialize',
		'callback',
		'_shop_css_head'
		);
}

/**
 * Insert the buy this link after the body of an entry
 */
function _shop_afterload_parse(&$entry) {

	if(PIVOTX_INWEBLOG!==true) {
		// killswitch if we're not in the frontend
		return;
	}

	if(!in_array('shop', $entry['category'])) {
		// killswitch if entry does not have shop category
		return;
	}

	
	global $PIVOTX;

	if($entry['extrafields']['item_is_available']=='yes') {
		if($PIVOTX['parser']->modifier['entry']) {
			$entry['buybutton'] = _shop_buybutton($entry, array('showqty'=>1, 'showlabels'=>1));
		} else {
			$entry['buybutton'] = _shop_buybutton($entry);
		}
		$entry['pricedisplay'] = _shop_pricedisplay($entry);
	} elseif($entry['extrafields']['item_is_available']=='no') {
		$entry['buybutton'] = _shop_soldoutbutton($entry);
		$entry['pricedisplay'] = _shop_pricedisplay($entry);
	} else {
		$entry['buybutton'] = '<!-- product availability unknown -->';
		$entry['pricedisplay'] = '<!-- price unknown -->';
	}

	// if the user selected automatic shop append the buybutton to the introduction
	if($PIVOTX['config']->get('shop_automatic')=='1') {
		$entry['introduction'] .= $entry['pricedisplay'];
		$entry['introduction'] .= $entry['buybutton'];
	}

}

/**
 * add css to head of page
 */
function _shop_css_head() {
	global $PIVOTX, $shop_cart_config;
	if($PIVOTX['config']->get('shop_builtin_css')!=false && !$shop_cart_config['css_inserted']) {
		$css_head = str_replace('[[extensionurl]]', $PIVOTX['paths']['extensions_url'], "\t".'<link href="[[extensionurl]]shop/css/pivotx.shop.css" rel="stylesheet" type="text/css" media="screen" />');
		$PIVOTX['extensions']->addHook('after_parse', 'insert_before_close_head', $css_head);
		$shop_cart_config['css_inserted'] = true;
	}
}

function _shop_load_hook($action='prepare', $id='mollie') {
	switch($action) {
		case 'payment_info':
			return '_'.$id.'_payment_info';
		case 'prepare':
			return '_'.$id.'_prepare_payment';
		case 'report':
			return '_'.$id.'_report_page';
		case 'return':
			return '_'.$id.'_return_page';
		default:
			return '_shop_hook_failure';
	}
}

/**
 * Show the price
 */
function _shop_pricedisplay($entry) {
	if($entry['extrafields']['item_price']) {
		$price_excl_tax = $entry['extrafields']['item_price'];
		$price_incl_tax = $price_excl_tax + ($price_excl_tax*$entry['extrafields']['item_tax']);
	} else {
		return '<!-- price is not available -->';
	}
	
	return ShopCart::renderPrice($price_incl_tax);
}

/**
 * Show the price * number of items
 */
function _shop_pricetotaldisplay($entry, $no_items=1) {
	if($entry['extrafields']['item_price']) {
		$price_excl_tax = $entry['extrafields']['item_price']*$no_items;
		$price_incl_tax = $price_excl_tax + ($price_excl_tax*$entry['extrafields']['item_tax']);
	} elseif($entry['item_price']) {
		$price_excl_tax = $entry['item_price']*$no_items;
		$price_incl_tax = $price_excl_tax + ($price_excl_tax*$entry['item_tax_percentage']);
	} else {
		return '<!-- price is not available -->';
	}
	
	return ShopCart::renderPrice($price_incl_tax);

}

/**
 * prepare an add to cart button
 */
function _shop_buybutton(&$entry, $params=null) {
	if(empty($params['text'])) {
		$params['text'] = st('Add to cart');
		$params['class'] = 'simple';
	}
	return _shop_buythisbutton($entry, $params);
}

/**
 * prepare a sold out button
 */
function _shop_soldoutbutton(&$entry, $params=null) {
	if(empty($params['text'])) {
		$params['text'] = st('Sold out');
	}
	if(empty($params['template'])) {
		$params['template'] = '<div id="buythisbutton-[[uid]]" class="[[class]]"><span>[[text]]</span></div>';
	}
	return _shop_buythisbutton($entry, $params);
}


/**
 * function to render the link for a given entry/productid
 *
 * return a link to a page with an orderform
 */
function _shop_removelink($entry, $variant=null) {
	global $PIVOTX;
	
	if($variant==null) {
		$link = $PIVOTX['paths']['site_url'].'index.php?action=remove&amp;entry='.$entry['uid'].'&amp;item_code='.$entry['extrafields']['item_code'].'&amp;item_option='.$entry['option_key'];
	} else {
		$link = $PIVOTX['paths']['site_url'].'index.php?action=remove&amp;entry='.$entry['uid'].'&amp;item_code='.$entry['extrafields']['item_code'].'&amp;item_option='.$variant;
	}
	
	return $link;
}

/**
 * function to render the link for a users cart
 *
 * return a link to a page with an orderform
 */
function _shop_cartlink() {
	global $PIVOTX;
	
	$link = $PIVOTX['paths']['site_url'].'index.php?action=cart';
	
	return $link;
}

/**
 * function to render the link for a given entry/productid
 *
 * return a link to a page with an orderform
 */
function _shop_checkoutlink() {
	global $PIVOTX;
	
	$link = $PIVOTX['paths']['site_url'].'index.php?action=checkout';
	
	return $link;
}


/**
 * format a button for the add to cart function given some parameters
 *
 * @param mixed $entry a fully loaded entry
 * @param array $params override defaults in a named array
 *
 * FIXME: make it templateable
 */
function _shop_buythisbutton($entry, $params) {
	global $PIVOTX;
	
	//debug_printr($params);
	if(!empty($params['class'])) {
		$params['class'] = safeString($params['class']);
	} else {
		$params['class'] = '';
	}
	if(!empty($params['action'])) {
		$params['action'] = safeString($params['action']);
		$params['no_type'] = 'text';
	} else {
		$params['action'] = 'add';
		$params['no_type'] = 'hidden';
	}
	if($params['showqty']) {
		$params['no_type'] = 'text';
	}
	if(!$params['no_items']) {
		$params['no_items'] = 1;
	}
	
	$params['formid'] = $entry['uid'];

	// example output
	$output = file_get_contents($PIVOTX['paths']['extensions_path'].'shop/templates/buythisbuttonform.tpl');

	// might be overridden
	if(!empty($params['template'])) {
		$output = $params['template'];
	}

	// show amount on form - or hide it
	$amount_options = "\t".'<input type="[[no_type]]" name="item_no_items" value="[[no_items]]" class="productamount" />';
	// show labels on form
	if($params['showlabels']) {
		$amount_options = "\t".'<label class="buythislabel amountlabel">'."\n\t".'<span>'.st('Choose an amount').'</span>'."\n\t".$amount_options."\n\t".'</label>';
	}
	$output = str_replace("[[amount_options]]", $amount_options, $output);

	// product options
	$product_options_arr = _shop_load_entry_options($entry);
	if($product_options_arr) {
		if(!empty($params['option_key'])) {
			$params['formid'] = $entry['uid'] .'_'. $params['option_key'];
			$product_options =  "\t" . '<input type="hidden" name="item_product_options" value="'.$params['option_key'].'" />';
		} else {
			foreach($product_options_arr as $key => $label) {
				$product_options_str .= "\t\t" . '<option value="'.$key.'">'.$label.'</option>'."\n";
			}
			$product_options = '
	<select name="item_product_options" class="productoptions">
[[product_options]]
	</select>
';
			$product_options = str_replace("[[product_options]]", $product_options_str, $product_options);
		}
		// show labels on form
		if($params['showlabels']) {
			$product_options = "\t".'<label class="buythislabel optionlabel">'."\n\t".'<span>'.st('Choose a size').'</span>'."\n\t".$product_options."\n\t".'</label>';
		}
		$output = str_replace("[[product_options]]", $product_options, $output);
	}
	

	
    foreach($entry as $key=>$value) {
        $output = str_replace("[[".$key."]]", $value, $output);
    }
	foreach($entry['extrafields'] as $key=>$value) {
        $output = str_replace("[[".$key."]]", $value, $output);
    }
    foreach($params as $key=>$value) {
        $output = str_replace("[[".$key."]]", $value, $output);
    }
    // Don't keep any [[whatever]]'s hanging around..
    $output = preg_replace("/\[\[([a-z0-9_-]+)\]\]/i", "", $output);
	return $output;
}

function _shop_load_entry_options($entry) {
	// product options
	if(!empty($entry['extrafields']['item_product_options'])) {
		$tmp_options = split("\n", $entry['extrafields']['item_product_options']);
		//debug_printr($tmp_options);
		foreach($tmp_options as $tmp_option) {
			list($key, $label) = explode("::", $tmp_option);
			$product_options_arr[trim($key)] = st(trim($label));
		}
		
		return $product_options_arr;
	}
	return false;
}

/**
 * format a checkoutform given some parameters
 *
 * @param array $checkout override defaults in a named array
 * 
 * FIXME: make it templateable
 */
function _shop_checkoutbutton($checkout) {

	if(!empty($checkout['text'])) {
		$checkout['text']=safeString($checkout['text']);
	} else {
		$params['text'] = st('Checkout');
	}
	if(!empty($checkout['class'])) {
		$checkout['class']=safeString($checkout['class']);
	} else {
		$params['class'] = 'button';
	}
	if(!empty($checkout['action'])) {
		$checkout['action']=safeString($checkout['action']);
	} else {
		$checkout['action'] = 'validate';
	}

	// example output
	$output = file_get_contents($PIVOTX['paths']['extensions_path'].'shop/templates/checkoutbuttonform.tpl');

	// might be overridden
	if(!empty($params['template'])) {
		$output = $params['template'];
	}
    foreach($checkout as $key=>$value) {
        $output = str_replace("[[".$key."]]", $value, $output);
    }
    // Don't keep any [[whatever]]'s hanging around..
    $output = preg_replace("/\[\[([a-z0-9_-]+)\]\]/i", "", $output);
	return $output;
}


/**
 * Add a hook to insert the shopping cart at the widgets
 */
if($PIVOTX['config']->get('shop_enabled')!=false) {
	$this->addHook(
		'widget',
		'callback',
		'_shop_show_auto_cart'
		);
}

/**
 * Render the shopping cart for the widgets hook
 */
function _shop_show_auto_cart() {
	global $PIVOTX;
	// if the user selected automatic shop append the buybutton to the introduction
	if($PIVOTX['config']->get('shop_automatic')=='1') {
		$output = _shop_show_cart('compact');
		return '<div class="shoppingcartcontainer">'.$output.'</div>';
	}
	return;
}

/**
 * check if the cart is empty.
 */
function _shop_check_cart() {
	global $PIVOTX;
	$cartnotempty = $PIVOTX['shoppingcart']->notEmpty();
	if($cartnotempty) {
		return true;
	}
	return false;

}

/**
 * Render the shopping cart in different versions
 * But only do it the first time unless forced
 *
 * @param string $size full|compact|checkout|small
 * @param bool $force override the single display
 */
function _shop_show_cart($size = 'full', $force = false) {
	global $PIVOTX, $shop_cart_config;

    if (!$shop_cart_config['js_inserted'] || $force) {
		if(!$shop_cart_config['js_inserted']) {
			// we always want to see jquery
			$PIVOTX['extensions']->addHook('after_parse', 'callback', 'jqueryIncludeCallback');
			
			$js_head = str_replace('[[extensionurl]]', $PIVOTX['paths']['extensions_url'], '<script type="text/javascript" src="[[extensionurl]]shop/js/pivotx.cart.js"></script>');
			$PIVOTX['extensions']->addHook('after_parse', 'insert_before_close_head', $js_head);
			$shop_cart_config['js_inserted'] = true;
		}

		// $size == full|compact|checkout|small
		$PIVOTX['shoppingcart']->setDisplay($size);

		$cartnotempty = $PIVOTX['shoppingcart']->notEmpty();

		if(!$cartnotempty) {
			$output = "<h4>".st("Shopping cart")."</h4> ";
			$output .= '<p class="totals">'.st("Cart is empty").'</p> ';
			$shop_homepagelink = $PIVOTX['config']->get('shop_default_homepage');
			$output .= '<p>';
			$output .= '<a href="'.$shop_homepagelink.'" class="continue_shopping continuebutton button">'.st('Continue shopping').'</a>';
			$output .= '</p> ';
			$cartoutput = '<div class="shoppingcart shoppingcart-'.$size.'">'.$output.'</div>';
			
			return $cartoutput;
		}
		
		// calculate shipping on non empty carts
		if($PIVOTX['config']->get('shop_use_shipping')==true) {
			//$cart = _shop_total_shipping($cart);
			$PIVOTX['shoppingcart']->calculateShipping();
		}
		// calculate discounts on non empty carts
		if($PIVOTX['config']->get('shop_use_discounts')==true) {
			$PIVOTX['shoppingcart']->calculateDiscounts();
		}
		
		$PIVOTX['shoppingcart']->calculateTotals();
		//debug_printr($PIVOTX['shoppingcart']);

		$cartoutput = $PIVOTX['shoppingcart']->renderCart();
	} else {
		$cartoutput = "<!-- cart is already shown on another part of the page -->";
	}
	
	return $cartoutput;
}

/**
 * Render the order in different versions
 *
 * @param string $size full|compact|checkout|small
 * @param array $orderparms
 */
function _shop_show_order($size = 'full', $orderparms=array()) {
	global $PIVOTX;

	if(!isset($PIVOTX['order'])) {
		// prepare missing order params
		if(!$orderparms['order_id']) {
			$orderparms['order_id'] = $PIVOTX['session']->getValue('order_id');
		}
		// load order into order variable
		// we can only have one!
		$PIVOTX['order'] = new ShopCart();
		$order = $PIVOTX['order']->loadOrder($orderparms['order_id']);
	}

	// $size == full|compact|checkout|small
	$PIVOTX['order']->setDisplay($size);

	$ordernotempty = $PIVOTX['order']->notEmpty();

	// whoops, empty order
	if(!$ordernotempty) {
		$output = "<h4>".st("Order")."</h4> ";
		$output .= '<p class="totals">'.st("Order is empty").'</p> ';
		$shop_homepagelink = $PIVOTX['config']->get('shop_default_homepage');
		$output .= '<p><a href="'.$shop_homepagelink.'">'.st('Continue shopping').'</a></p> ';
		$orderoutput = '<div class="shoppingcart shoppingcart-'.$size.'">'.$output.'</div>';
		
		return $orderoutput;
	}
	

	$orderoutput = $PIVOTX['order']->renderCart();

	return $orderoutput;
}

/**
 * Load an order from session or transactionid
 *
 * @params array $params
 */
function _shop_load_order($params) {
	global $PIVOTX;

	if($params['transaction_id'] && !$params['order_id']) {
		$shopdb = new ShopSql();
		$orderfromdb = $shopdb->getOrderByPayment($params['transaction_id']);
		
		$params['order_id'] = $orderfromdb['order_id'];
		$params['order_public_code'] = $orderfromdb['order_public_code'];
		$params['order_public_hash'] = $orderfromdb['order_public_hash'];
	}
	
	if($params['order_public_code'] && $params['order_public_hash'] && !$params['order_id']) {
		$shopdb = new ShopSql();
		$orderfromdb = $shopdb->getOrderByCode($params['order_public_code'], $params['order_public_hash']);
		
		$params['order_id'] = $orderfromdb['order_id'];
	}
	
	if(!isset($PIVOTX['order'])) {

		// prepare missing order params
		if(!$params['order_id']) {
			$params['order_id'] = $PIVOTX['session']->getValue('order_id');
		}
		// load order into order global
		// we can only have one!
		$PIVOTX['order'] = new ShopCart('order', $params['order_id']);

		
	}
	$order = $PIVOTX['order'];

	return $order;
}

/**
 * TODO: Remove function
 * Calculate the total amount of a shopping cart or an order
 *
 * @param array $cart the shopping cart
 * @param array $entries optional array of entries in the shopping cart
 *
 * FIXME: order total fiksen met product options
 */
function _shop_cart_total_amounts($cart, $entries=array()) {
	global $PIVOTX;

	$currency = $PIVOTX['config']->get('shop_currency', 'EUR');
	$currencies = _shop_currency_types();
	$currency = $currencies[$currency];

	$template = file_get_contents($PIVOTX['paths']['extensions_path'].'shop/templates/pricedisplay.tpl');

	$totals = array();
	//debug_printr($totals);
	if($PIVOTX['config']->get('shop_use_shipping')==0 && $cart['shipping']['total']['amount']>0) {
		$shipping_incl_tax = $cart['shipping']['total']['amount'];
		$shipping_tax_rate = $PIVOTX['config']->get('shop_shipping_tax_rate', '0.19');
		//$shipping_excl_tax = $shipping_incl_tax / (1+$shipping_tax_rate);
		//$shipping_tax = $cart['shipping']['total']['amount'] - $shipping_excl_tax;
		$shipping_tax = $shipping_incl_tax * (1 - ( 1 / (1+$shipping_tax_rate)));
		$shipping_excl_tax = $shipping_incl_tax - $shipping_tax;
		/*
		debug_printr(array(
			'$shipping_incl_tax' => $shipping_incl_tax,
			'$shipping_tax_rate' => $shipping_tax_rate,
			'$shipping_excl_tax' => $shipping_excl_tax,
			'$shipping_tax' => $shipping_tax,
			$totals
		));
		*/
		$totals['price_excl_taxes'] = $totals['price_excl_taxes'] + $shipping_excl_tax;
		$totals['taxes'][$shipping_tax_rate] = $totals['taxes'] + $shipping_tax;
		$totals['price_incl_taxes'] = round($totals['price_incl_taxes'] + $shipping_incl_tax);
	} 
	//debug_printr($totals);
	foreach($cart['items'] as $key => $no_items) {
		if(!isset($entries[$key]) || empty($entries[$key])) {
			$entries[$key] = $PIVOTX['db']->read_entry($key);
		}
		$totals['items'] = $totals['items'] + $no_items;
		$item_price = $entries[$key]['extrafields']['item_price'];
		$item_tax = $entries[$key]['extrafields']['item_tax'];
		$totals['price_excl_taxes'] = $totals['price_excl_taxes'] + ($no_items * $item_price);
		$totals['taxes'][$item_tax] = $totals['taxes'][$item_tax] + ($no_items * ($item_price * $item_tax));
		$totals['price_incl_taxes'] = round($totals['price_incl_taxes'] + ($no_items * ($item_price + ($item_price * $item_tax))));

	}
	//debug_printr($totals);
	/**
	 * FIXME: make it templateable
	 */
	if(1) { // switch to only build html if we want html

		// total excl taxes
		$tet = str_replace('[[currency]]', $currency, $template);
		$amount = number_format($totals['price_excl_taxes']/100, 2, ',', '.');
		$tet = '<div class="total_excl_taxes"><span class="label">'.st('Excl.').' '.st('VAT').' </span> ' . str_replace('[[amount]]', $amount, $tet)."</div>";

		// total taxes
		$taxes = "";
		foreach($totals['taxes'] as $tax_percentage => $amount) {
			$tt = str_replace('[[currency]]', $currency, $template);
			$display_tax = '<span class="label taxpercentage">'.st('VAT').' '.($tax_percentage*100) . "%</span> ";
			$amount = number_format($amount/100, 2, ',', '.');
			$tt = str_replace('[[amount]]', $amount, $tt);
			$taxes .= '<div class="taxes">'.$display_tax . $tt ."</div>";
		}
		
		// total incl taxes
		$tit = str_replace('[[currency]]', $currency, $template);
		$amount = number_format($totals['price_incl_taxes']/100, 2, ',', '.');
		$tit = str_replace('[[amount]]', $amount, $tit);
		$compacttotal =  '<div class="total_incl_taxes"><span class="label">'.st('Total').' </span> '. $tit ."</div>";
		$tit = '<div class="total_incl_taxes"><span class="label">'.st('Incl.').' '.st('VAT').' </span> '. $tit ."</div>";
		
		$totals['display_amount_full'] = $tet . "\n". $taxes . "\n" . $tit;
		$totals['display_amount_compact'] = $compacttotal;
		$totals['display_amount_checkout'] = $totals['display_amount_full'];
		$totals['display_amount_minimal'] = $compacttotal;
	}
	
	//debug_printr($totals);

	return $totals;
}

/**
 * validate form data for the checkout form
 */
function _shop_validate_checkoutform($formdata=false) {
	global $PIVOTX;

	//debug_printr($formdata);
	$posted_keys = array_keys($formdata);

	$defaultvalues = _shop_get_defaultcheckoutform();
	
	//debug_printr($defaultvalues);
	
	// check if the posted vars are renamed in the form defaultvalues
	foreach($defaultvalues as $key => $value) {
		if((substr($key, -5, 5) == '_name') && in_array($value, $posted_keys)) {
			$formdata[substr($key, 0, -5)] = $formdata[$value];
			unset($formdata[$value]);
		}
	}
	//debug_printr($formdata);
	
	$outdata['errors'] = 0;
	foreach($formdata as $key => $value) {
		switch($key) {
			case 'user_name':
				if(empty($value) || !is_string($value) || strlen($value)<3) {
					$outdata[$key.'_haserror'] = 'haserror';
					$label = $defaultvalues[$key.'_label'];
					$outdata[$key.'_errormessage'] = sprintf(st('Please enter a valid %s.'), $label);
					$outdata['errors']++;
				}
				break;
			case 'user_email':
				if(empty($value) || !_shop_isEmail($value)) {
					$outdata[$key.'_haserror'] = 'haserror';
					$label = $defaultvalues[$key.'_label'];
					$outdata[$key.'_errormessage'] = sprintf(st('Please enter a valid %s.'), $label);
					$outdata['errors']++;
				}
				break;
			case 'user_address_address':
			case 'user_address_city':
				if(empty($value) || !is_string($value) || strlen($value)<3) {
					$outdata[$key.'_haserror'] = 'haserror';
					$label = $defaultvalues[$key.'_label'];
					$outdata[$key.'_errormessage'] = sprintf(st('Please enter a valid %s.'), $label);
					$outdata['errors']++;
				}
				break;
			case 'user_address_postcode':
				$value = strtoupper(str_replace(' ', '', $value));
				$formdata[$key] = $value;
				if(empty($value) || !(preg_match("/^[0-9]{4}[A-Z]{2}$/", $value, $match))) {
					$outdata[$key.'_haserror'] = 'haserror';
					$label = $defaultvalues[$key.'_label'];
					$outdata[$key.'_errormessage'] = sprintf(st('Please enter a valid %s.'), $label);
					$outdata['errors']++;
				}
				break;
			case 'user_address_telephone':
				if(!empty($value) && !(preg_match("/^([0-9\(\) +-]{8,})$/", $value, $match))) {
					$outdata[$key.'_haserror'] = 'haserror';
					$label = $defaultvalues[$key.'_label'];
					$outdata[$key.'_errormessage'] = sprintf(st('Please enter a valid %s number.'), $label);
					$outdata['errors']++;
				}
				break;
		}
		
		if(!empty($outdata[$key.'_errormessage'])) {
			$outdata[$key.'_errormessage'] = '<p class="errormessage">'.$outdata[$key.'_errormessage'].'</p>';
		}
	}
	if($PIVOTX['config']->get('shop_use_shipping')==true) {
		if(!isset($formdata['shipping_handler'])) {
			$outdata['shipping_handler_haserror'] = 'haserror';
			$label = $defaultvalues['shipping_handler_label'];
			$outdata['shipping_handler_errormessage'] =  '<p class="errormessage">'.sprintf(st('Please select a %s.'), $label).'</p>';
			$outdata['errors']++;
		} else {
			$formdata['shipping_handler_' . $formdata['shipping_handler'] .'_selected'] = 'checked="checked"';
		}
	}
	$shop_use_payment = $PIVOTX['config']->get('shop_use_payment');
	if($shop_use_payment!='no') {
		if(!isset($formdata['payment_provider'])) {
			$outdata['payment_provider_haserror'] = 'haserror';
			$label = $defaultvalues['payment_provider_label'];
			$outdata['payment_provider_errormessage'] =  '<p class="errormessage">'.st(sprintf('Please select a %s.', $label)).'</p>';
			$outdata['errors']++;
		} else {
			$formdata['payment_provider_' . $formdata['payment_provider'] .'_selected'] = 'checked="checked"';
		}
	}
	
	$outdata = array_merge($formdata, $outdata);
	
	//debug_printr($outdata);
	return $outdata;
}

/**
 * show the checkoutform
 *
 * FIXME: make it pluginable - shipping, newsletter signup, giftcard, etc.
 * FIXME: make it templateable
 */
function _shop_show_checkoutform($formdata=false) {
	global $PIVOTX;
	//debug('checkoutform formdata:');
    //debug_printr($formdata);
	//debug('checkoutform post:');
	//debug_printr($_POST);
	
	// get the flat defaultform
	$defaultcheckoutformdata = _shop_get_defaultcheckoutform();
	//debug('default checkout formdata:');
	//debug_printr($defaultcheckoutformdata);
	$formdata = array_merge($defaultcheckoutformdata, $formdata);
	
	$submitted_data = cleanParams($_POST);
	$formdata = array_merge($formdata, $submitted_data);
	
	//debug('checkoutform formdata after merge:');
    //debug_printr($formdata);

	if(!empty($formdata['action'])) {
		$formdata['action'] = safeString($formdata['action']);
	} else {
		$formdata['action'] = 'validate';
	}
	// example output
	
	$formdata['checkouttitle'] = st('Please enter your name and address.');
	$formdata['backlinkname'] = st('&laquo; go back');

// the start of the form
	$template['header'] = '
<div id="checkoutform" class="[[class]]"><form method="post" action=""><fieldset>
	<h4>[[checkouttitle]]</h4>
	<input type="hidden" name="action" value="[[action]]" />
';

	
	// default address form
	$template['address'] = file_get_contents($PIVOTX['paths']['extensions_path'].'shop/templates/addressform.tpl');

	// shipping and handling
	$shop_shipping_handler = $PIVOTX['config']->get('shop_shipping_handler', 'no');
	$shop_use_shipping = $PIVOTX['config']->get('shop_use_shipping', 'no');
	if($shop_shipping_handler!='no' && stristr($shop_use_shipping, 'handler')) {
		//debug_printr($shop_shipping_handler);

		$has_shipping_store_pickup = (stristr($shop_shipping_handler, 'storepickup'))?1:0;
		$has_shipping_postnl_mail = (stristr($shop_shipping_handler, 'postnl'))?1:0;
		$has_shipping_tntexpress = (stristr($shop_shipping_handler, 'tntexpress'))?1:0;
		$has_shipping_ups = (stristr($shop_shipping_handler, 'ups'))?1:0;
		$has_shipping_fedex = (stristr($shop_shipping_handler, 'fedex'))?1:0;
		
		$shipping = explode('|', $PIVOTX['config']->get('shop_shipping_handler'));
		
		if(count($shipping)==1) {
			$template['shipping'] = '<input type="hidden" id="shipping_handler_default" name="shipping_handler" value="default" /><!-- no shipping handler options -->';
		} elseif(count($shipping)>1) {
			foreach($shipping as $shipping_method ) {
				//debug_printr($shipping_method);
				$template['shipping_options'][$shipping_method] = '
		<label class="radio_item_label">
			<span>[[shipping_handler_options_'.$shipping_method.'_label]]</span>
			<input type="radio" id="shipping_handler_'.$shipping_method.'" name="shipping_handler" value="[[shipping_handler_options_'.$shipping_method.'_value]]" class="[[radioclass]]" [[shipping_handler_options_'.$shipping_method.'_selected]] />
		</label>
';
			}
			$template['shipping'] = '
	<div class="formrow formrow_radios [[shipping_handler_haserror]]">
		<label for="shipping_handler" class="radio_group_label">[[shipping_handler_label]]</label>
		[[shipping_options]]
		[[shipping_handler_errormessage]]
		[[shipping_handler_text]]
	</div>
';
			$template['shipping'] = str_replace('[[shipping_options]]', join("\n", $template['shipping_options']), $template['shipping']);
			unset($template['shipping_options']);
		}
	} else {
		$template['shipping'] = '<input type="hidden" id="shipping_handler_default" name="shipping_handler" value="default" /><!-- no shipping handler options -->';
	}

	// payment
	$shop_use_payment = $PIVOTX['config']->get('shop_use_payment', 'no');
	if($shop_use_payment!='no') {		
		//$has_payment_mollie_ideal = (stristr($shop_use_payment, 'mollie'))?1:0;
		//$has_payment_other = (stristr($shop_use_payment, 'other'))?1:0;
		
		$payment = explode('|', $PIVOTX['config']->get('shop_use_payment'));
		
		if(count($payment)==1) {
			// exacly one payment provider
			foreach($payment as $payment_provider) {
				//debug_printr($payment_provider);
				$template['payment_options'][$payment_provider] = '
		<label class="radio_item_label">
			<span>[[payment_provider_options_'.$payment_provider.'_label]]</span>
			<input type="hidden" id="payment_provider_'.$payment_provider.'" name="payment_provider" value="[[payment_provider_options_'.$payment_provider.'_value]]" class="[[radioclass]]" [[payment_provider_options_'.$payment_provider.'_selected]] />
		</label>
';
			}
			$template['payment'] = '
	<div class="formrow formrow_radios [[payment_provider_haserror]]">
		<label for="payment_provider" class="radio_group_label">[[payment_provider_label]]</label>
		[[payment_options]]
		[[payment_provider_errormessage]]
	</div>
	';
			$template['payment'] = str_replace('[[payment_options]]', join("\n", $template['payment_options']), $template['payment']);
			unset($template['payment_options']);
		} else {
			foreach($payment as $payment_provider) {
				//debug_printr($payment_provider);
				$template['payment_options'][$payment_provider] = '
		<label class="radio_item_label">
			<span>[[payment_provider_options_'.$payment_provider.'_label]]</span>
			<input type="radio" id="payment_provider_'.$payment_provider.'" name="payment_provider" value="[[payment_provider_options_'.$payment_provider.'_value]]" class="[[radioclass]]" [[payment_provider_options_'.$payment_provider.'_selected]] />
		</label>
';
			}
			$template['payment'] = '
	<div class="formrow formrow_radios [[payment_provider_haserror]]">
		<label for="payment_provider" class="radio_group_label">[[payment_provider_label]]</label>
		[[payment_options]]
		[[payment_provider_errormessage]]
		[[payment_provider_text]]
	</div>
	';
			$template['payment'] = str_replace('[[payment_options]]', join("\n", $template['payment_options']), $template['payment']);
			unset($template['payment_options']);
		}
	} else {
		$template['payment'] = '<input type="hidden" id="payment_provider_default" name="payment_provider" value="default" /><!-- no payment provider options -->';
	}

// the end of the form
	$template['footer'] = '
	<div class="formrow formrow_submit">
		<label><a href="/?action=cart" class="continue_shopping">[[backlinkname]]</a></label>
		<button type="submit" name="checkout_submit" value="[[submitvalue]]" class="button">
			<span>[[submitvalue]]</span>
		</button>
	</div>
</fieldset></form></div>
';

	$formdata['submitvalue'] = st('Continue');


	// the option to override it all with extensions
    $PIVOTX['extensions']->executeHook('shop_after_checkoutform', $formdata);
	
	// might be overridden
	if(!empty($formdata['template'])) {
		$template = $formdata['template'];
	}
	
	$template = join('', $template);
    //debug($template);

    foreach($formdata as $key=>$value) {
        $template = str_replace("[[".$key."]]", $value, $template);
    }
    // Don't keep any [[whatever]]'s hanging around..
    $template = preg_replace("/\[\[([a-z0-9_-]+)\]\]/i", "", $template);
	$cart = _shop_show_cart('checkout', true);
	$output = $cart . $template;
	
	return '<div class="checkout">'.$output.'</div>';
}

/**
 * Prepare the default options for the checkoutform
 *
 * Uses http://www.w3.org/TR/P3P/#Data_Types for field names
 * Might use http://www.ietf.org/rfc/rfc3106 for field names
 */
function _shop_get_defaultcheckoutform() {
	global $PIVOTX;
	
	$outputarray = array();
	$defaultvalues = array(
		'user_name' => array(
			'type' => 'text',
			'name' => 'name',
			'required' => 'true',
			'validation' => 'ifany|minlength=2',
			'label' => st('Name'),
			'text' => st('Your first and last name'),
			'value' => '',
			'haserror' => '',
			'errormessage' => '',
		),
		'user_email' => array(
			'type' => 'text',
			'name' => 'email',
			'required' => 'true',
			'validation' => 'email',
			'label' => st('Email Address'),
			'text' => st('Your email address'),
			'value' => '',
			'haserror' => '',
			'errormessage' => '',
		),
		'user_address_address' => array(
			'type' => 'text',
			'name' => 'street',
			'required' => 'true',
			'validation' => 'ifany|minlength=2',
			'label' => st('Street Address'),
			'text' => st('Your main address'),
			'value' => '',
			'haserror' => '',
			'errormessage' => '',
		),
		'user_address_postcode' => array(
			'type' => 'text',
			'name' => 'postcode',
			'required' => 'true',
			'validation' => 'postcodenl',
			'label' => st('Postal Code'),
			'text' => st('Your postal code'),
			'value' => '',
			'haserror' => '',
			'errormessage' => '',
		),
		'user_address_city' => array(
			'type' => 'text',
			'name' => 'city',
			'required' => 'true',
			'validation' => 'ifany|minlength=2',
			'label' => st('City'),
			'text' => st('Your home city'),
			'value' => '',
			'haserror' => '',
			'errormessage' => '',
		),
		'user_address_country' => array(
			'type' => 'text',
			'name' => 'country',
			'required' => 'true',
			'validation' => 'ifany|minlength=2',
			'label' => st('Country'),
			'text' => st('Your home country'),
			'value' => '',
			'haserror' => '',
			'errormessage' => '',
		),
		'user_phone' => array(
			'type' => 'text',
			'name' => 'telephone',
			'required' => 'true',
			'validation' => 'phone',
			'label' => st('Phone Number'),
			'text' => st('Your phone number'),
			'value' => '',
			'haserror' => '',
			'errormessage' => '',
		),
		'radioclass' => 'input_radio',
		'textclass' => 'input_text',
	);
	// hook for address plugins
	// - these might change all address fields
	// - add shipping and billing addresses
	// - change labels and validation
	$PIVOTX['extensions']->executeHook('shop_address_defaults', $defaultvalues);

	$shop_use_shipping = $PIVOTX['config']->get('shop_shipping_handler', 'no');
	if($shop_use_shipping!='no') {
		//debug_printr($shop_use_shipping);
		$has_shipping_store_pickup = (stristr($shop_use_shipping, 'storepickup'))?1:0;
		$has_shipping_postnl_mail = (stristr($shop_use_shipping, 'postnl'))?1:0;
		$has_shipping_tntexpress = (stristr($shop_use_shipping, 'tntexpress'))?1:0;
		$has_shipping_ups = (stristr($shop_use_shipping, 'ups'))?1:0;
		$has_shipping_fedex = (stristr($shop_use_shipping, 'fedex'))?1:0;

		$defaultvalues['shipping_handler'] = array(
			'type' => 'radio',
			'required' => 'true',
			'validation' => '',
			'label' => st('Shipping handler'),
			'text' => st('Please choose a shipping handler'),
			'value' => '',
			'haserror' => '',
			'errormessage' => '',
		);
		
		if($has_shipping_store_pickup) {
			$defaultvalues['shipping_handler']['options']['storepickup'] = array(
							'label' => st('Store pickup'),
							'value' => 'storepickup',
							'text' => st('Choose this option to pickup your goods at the store.'),
							'charge_incl_tax' => 0,
							'charge_excl_tax' => 0,
							'tax_amount' => 0
						);
		}
		if($has_shipping_postnl_mail) {
			$defaultvalues['shipping_handler']['options']['postnl'] = array(
							'label' => st('PostNL'),
							'value' => 'postnl',
							'text' => st('Choose this option to use PostNL mail.'),
							'charge_incl_tax' => 500,
							'charge_excl_tax' => 420,
							'tax_amount' => 80
						);
		}
		if($has_shipping_tntexpress) {
			$defaultvalues['shipping_handler']['options']['tntexpress'] = array(
							'label' => st('TNT Express'),
							'value' => 'tntexpress',
							'text' => st('Choose this option to use TNT Express mail.'),
							'charge_incl_tax' => 1190,
							'charge_excl_tax' => 1000,
							'tax_amount' => 190
						);
		}
		if($has_shipping_ups) {
			$defaultvalues['shipping_handler']['options']['ups'] = array(
							'label' => st('UPS'),
							'value' => 'ups',
							'text' => st('Choose this option to use UPS.'),
							'charge_incl_tax' => 1190,
							'charge_excl_tax' => 1000,
							'tax_amount' => 190
						);
		}
		if($has_shipping_fedex) {
			$defaultvalues['shipping_handler']['options']['fedex'] = array(
							'label' => st('FedEx'),
							'value' => 'fedex',
							'text' => st('Choose this option to use FedEx.'),
							'charge_incl_tax' => 1190,
							'charge_excl_tax' => 1000,
							'tax_amount' => 190
						);
		}
		// hook for shipping plugins
		$PIVOTX['extensions']->executeHook('shop_shipping_handlers', $defaultvalues);
	}
	
	$shop_use_payment = $PIVOTX['config']->get('shop_use_payment', 'no');
	if($shop_use_payment!='no') {		
		$has_payment_other = (stristr($shop_use_payment, 'other'))?1:0;
		
		$defaultvalues['payment_provider'] = array(
			'type' => 'radio',
			'required' => 'true',
			'validation' => '',
			'label' => st('Payment provider'),
			'text' => st('Please choose a payment provider'),
			'value' => '',
			'haserror' => '',
			'errormessage' => '',
		);

		if($has_payment_other) {
			$defaultvalues['payment_provider']['options']['other'] = array(
							'label' => st('Offline payment'),
							'value' => 'other',
							'text' => st('Offline payment'),
							'charge_incl_tax' => 0,
							'charge_excl_tax' => 0,
							'tax_amount' => 0
						);
		}
		// hook for payment plugins
		$PIVOTX['extensions']->executeHook('shop_payment_methods', $defaultvalues);
	}


	// and a make everything change for the checkoutform
	// pass by reference dudes 
    $PIVOTX['extensions']->executeHook('shop_default_checkoutform', $defaultvalues);
	
    // flatten the array

	foreach($defaultvalues as $key => $value) {
		if(is_array($value)) {
			foreach($value as $skey => $svalue) {
				if(is_array($svalue)) {
					foreach($svalue as $sskey => $ssvalue) {
						if(is_array($ssvalue)) {
							foreach($ssvalue as $ssskey => $sssvalue) {
								$outputarray[$key.'_'.$skey.'_'.$sskey.'_'.$ssskey] = $sssvalue;
							}
						} else {
							$outputarray[$key.'_'.$skey.'_'.$sskey] = $ssvalue;
						}
					}
				} else {
					$outputarray[$key.'_'.$skey] = $svalue;
				}
			}
		} else {
			$outputarray[$key] = $value;
		}
	}
	foreach($outputarray as $key => $value) {
		if(stristr($key, '_text')) {
			$outputarray[$key] = '<p class="description">'.$value.'</p>';
		}
	}
	
	return $outputarray;	
}


/**
 * save a shopping cart into an order
 *
 * @param array $inorder partially structured order
 * return full structured order
 */
function _shop_save_order($inorder=false) {
	global $PIVOTX;
	
	$order = false;
	//debug('inorder');
	//debug_printr($inorder);
	
	if($inorder) {
		$shopdb = new ShopSql();

		if(empty($inorder['order_public_code'])) {
			// this should be unique enough
			$inorder['order_public_code'] = time().'.'.uniqid();
			//$inorder['order_public_hash'] = sha1($inorder['order_public_code'].$inorder['pivotxsession']);
		}
		if(empty($inorder['user_ip'])) {
			$inorder['user_ip'] = $_SERVER['REMOTE_ADDR'];
			$inorder['user_hostname'] = ($_SERVER['REMOTE_HOST'])?$_SERVER['REMOTE_HOST']:$_SERVER['REMOTE_ADDR'];
			$inorder['user_browser'] = $_SERVER['HTTP_USER_AGENT'];
		}

		if(!$inorder['order_items'] || !is_array($inorder['order_items'])) {
			// $size == full|compact|checkout|small

			$cartnotempty = $PIVOTX['shoppingcart']->notEmpty();
			// calculate shipping on non empty carts
			if($PIVOTX['config']->get('shop_use_shipping')==true) {
				//$cart = _shop_total_shipping($cart);
				$PIVOTX['shoppingcart']->calculateShipping();
			}
			// calculate discounts on non empty carts
			if($PIVOTX['config']->get('shop_use_discounts')==true) {
				$PIVOTX['shoppingcart']->calculateDiscounts();
			}

			$PIVOTX['shoppingcart']->calculateTotals();
			$inorder['order_items'] = $PIVOTX['shoppingcart']->loadCartItems();

		} else {
			//debug('order already has items, not loading them again');
			//debug_printr($inorder['order_items']);
		}
		
		//debug('shoppingcart');
		//debug_printr($PIVOTX['shoppingcart']);
		
		//debug('inorder before save');
		//debug_printr($inorder);
		$order = $shopdb->saveOrder($inorder);
		
		//debug('order after save');
		//debug_printr($order);
		
		if(empty($order['order_public_hash'])) {
			//debug('adding public hash to order');
			$order['order_public_hash'] = sha1($order['order_public_code'].$order['order_id']);
			//debug_printr($order);
			$order = $shopdb->saveOrder($order);
		}
	}
	$PIVOTX['session']->setValue('order_id', $order['order_id']);
	$PIVOTX['session']->setValue('order_public_code', $order['order_public_code']);
	$PIVOTX['session']->setValue('order_public_hash', $order['order_public_hash']);
	
	// remove the cart, bang!
	if($PIVOTX['shoppingcart']) {
		_shop_remove_cart();
	}
	
	//debug('outorder');
	//debug_printr($order);
	return $order;
}

/**
 * Show the summary of an order
 *
 * TODO: clean this up
 */
function _shop_order_summary($order) {
	global $PIVOTX;
	
	//$output = '<pre>'.var_export($order, true).'</pre>';
	$output = _shop_order_user($order);
	

	$output .= "<h4>".st("Order summary")."</h4>";
	$output .= "<p>".st("Order date").": ".date('D, d M Y', strtotime($order['order_datetime']))."</p>";
	//$cart = $order['order_items'];
	$cart['items'] = array();

	if(is_array($order['order_items']) && count($order['order_items'])>0) {
		$output .= '<table class="order_summary">';
		foreach($order['order_items'] as $xkey => $entry) {
			if(stristr($item_id, "::")) {
				list($item_id, $key_option) = explode("::", $item_id);
			} else {
				$key_option = false;
			}
			$key = $entry['item_id'];
			$key_option = $entry['item_option'];
			if($key_option) {
				$key = $key ."::". $key_option;
			}

			$cart['items'][$key] = $entry['order_no_items'];

			// load entry if it exists
			$entries[$key] = $PIVOTX['db']->read_entry($entry['item_id']);
			//debug_printr($entries[$key]);
			if($key_option) {
				$entries[$key]['item_product_options'] = _shop_load_entry_options($entries[$key]);
				$entries[$key]['option_key'] = $key_option;
				$entries[$key]['option_label'] = $entries[$key]['item_product_options'][$key_option];
			
				$option_label = ' <span class="option">'.$entries[$key]['option_label'].'</span>';

			} else {
				$option_label = '';
			}

			$entries[$key]['pricedisplay'] = ($entries[$key]['pricedisplay'])?$entries[$key]['pricedisplay']:_shop_pricedisplay($entries[$key]);
			$oddclass = !($oddclass)?'odd':'';
			$output .= '<tr class="'.$oddclass.'">';
			$output .= '<td><span>'. $entries[$key]['title'] . $option_label .'</span></td>';
			$output .= '<td class="singleprice">'.$entries[$key]['pricedisplay'].'</td>';
			$output .= '<td>'.st('&times;').'</td>';
			$output .= '<td><strong>'.$entry['order_no_items'].' </strong></td>';
			$output .= '<td>&#61;</td>';
			$totaldisplay = _shop_pricetotaldisplay($entries[$key], $entry['order_no_items']);
			$output .= '<td class="totalprice">'.$totaldisplay.'</td>';
			$num += $entry['order_no_items'];
			$output .= '</tr>';
		}
		
		$output .= '<tr class="totals"><td colspan="6"><em>'.$num.'</em> '.st('items in order').'.</td></tr>';

		if($PIVOTX['config']->get('shop_use_shipping')==true) {
			$cart = _shop_total_shipping($cart);
			$output .= '<tr class="totals odd"><td colspan="1"></td><td>'.st('Shipping costs').'</td><td colspan="4">'.$cart['shipping']['display']['full'].'</td></tr>';
		}
		
		$totals = _shop_cart_total_amounts($cart, $entries);
		$output .= '<tr class="totals odd"><td colspan="1"></td><td>'.st('Total').'</td><td colspan="4">'.$totals['display_amount_checkout'].'</td></tr>';
		$output .= "</table>";
	} else {
		$output .= '<p class="totals">'.st("Cart is empty").'</p>';
	}
	$output .= _shop_order_payment_status($order);
	
	return $output;
}

/**
 * show the personal details of a customer
 */
function _shop_order_user($order) {
	
	$output = "<h4>".st("Your personal data")."</h4>";
	$output .= '<table class="personaldata">';
	$output .= "<tr><td>".st("Name").":</td><td>".$order['user_name']."</td></tr>";
	$output .= "<tr><td>".st("Email").":</td><td>".$order['user_email']."</td></tr>";
	$output .= "<tr><td>".st("Phone").":</td><td>".((!empty($order['user_phone']))?$order['user_phone']:st('Not entered'))."</td></tr>";
	$output .= '<tr><td rowspan="3">'.st("Address").":</td><td>".nl2br($order['user_address'])."</td></tr>";
	$output .= "<tr><td>".$order['user_postcode'] .' '. $order['user_city']."</td></tr>";
	$output .= "<tr><td>".$order['user_country']."</td></tr>";	
	$output .= "</table>";

	return $output;
}

/**
 * show the payment status
 *
 * TODO: Better payment plugin support
 */
function _shop_order_payment_status($order) {
	global $PIVOTX;
	$hook = _shop_load_hook('payment_info', $order['payment_provider']);
	
	$paymentprovider = $PIVOTX['extensions']->executeHook($hook, $order['payment_provider']);
	
	if(
		$order['payment_provider'] == 'mollie'
		&& $order['payment_status'] == 'Success'
	) {
		$output = '<h4>'. st('Payment successful') .'</h4>';
		$output .= '<p>'. st('Payment handled by') .': '.$paymentprovider.'</p>';
	} elseif(
		$order['payment_provider'] == 'ogone'
		&& in_array($order['payment_status'], array('9: Payment requested','5: Authorized'))
	) {
		$output = '<h4>'. st('Payment successful') .'</h4>';
		$output .= '<p>'. st('Payment handled by') .': '.$paymentprovider.'</p>';
	} else {
		$output = '<h4>'. st('Payment incomplete') .'</h4>';
		$output .= '<p>'. st('You will receive a message with further instructions for payment.') .'</p>';
	}

	return '<div class="paymentinfo">'.$output."</div>";
}

/**
 * calculate the shipping amount
 *
 * @param array $cart the shopping cart
 * 
 * @return array $cart
 */
function _shop_total_shipping($cart) {
	global $PIVOTX;

	$currency = $PIVOTX['config']->get('shop_currency', 'EUR');
	$currencies = _shop_currency_types();
	$currency = $currencies[$currency];
	
	$template = file_get_contents($PIVOTX['paths']['extensions_path'].'shop/templates/pricedisplay.tpl');

	$shippingtype = explode(',', $PIVOTX['config']->get('shop_use_shipping'));
	//debug_printr($shippingtype);
	if(in_array('fixed', $shippingtype)) {
		$cart['shipping']['fixed']['amount'] = $PIVOTX['config']->get('shop_shipping_fixed_amount', '500');
	} else {
		$cart['shipping']['fixed']['amount'] = 0;
	}
	
	// get totals from all shipping
	// currently only fixed
	$cart['shipping']['total']['amount'] = $cart['shipping']['fixed']['amount'];
	
	// prettify output for totals
	$amount = number_format($cart['shipping']['fixed']['amount']/100, 2, ',', '.');
	$cart['shipping']['display']['full'] = str_replace('[[currency]]', $currency, $template);
	$cart['shipping']['display']['full'] = str_replace('[[amount]]', $amount, $cart['shipping']['display']['full']);
	
	return $cart;
}

/**
 * add an entry to the cart
 */
function _shop_add_to_cart($entry, $no_items, $option='default') {
	global $PIVOTX;
	
	if(is_array($entry) && isset($entry['uid'])) {	
		$item_id = $entry['uid'];
	} elseif(is_numeric($entry)) {
		$item_id = $entry;
	}
	
	$PIVOTX['shoppingcart']->addItem($item_id, $no_items, $option);
	return $PIVOTX['shoppingcart'];
}

/**
 * update an entry in the cart
 */
function _shop_update_cart($entry, $no_items, $option='default') {
	global $PIVOTX;

	if(is_array($entry) && isset($entry['uid'])) {	
		$item_id = $entry['uid'];
	} elseif(is_numeric($entry)) {
		$item_id = $entry;
	}

	$PIVOTX['shoppingcart']->updateItem($item_id, $no_items, $option);
	return $PIVOTX['shoppingcart'];
}

/**
 * delete an entry from the cart
 */
function _shop_remove_from_cart($entry, $option='default') {
	global $PIVOTX;
	
	if(is_array($entry) && isset($entry['uid'])) {	
		$item_id = $entry['uid'];
	} elseif(is_numeric($entry)) {
		$item_id = $entry;
	}
    //debug('trying to delete item: '. $item_id .' - '. $option);
	
	$PIVOTX['shoppingcart']->deleteItem($item_id, $option);
	return;
}

/**
 * delete the cart
 */
function _shop_remove_cart() {
	global $PIVOTX;
	$PIVOTX['shoppingcart']->resetCart();
	return $PIVOTX['shoppingcart'];
}

/**
 * get a mail template from the config depending on the current status
 */
function _shop_get_mailtemplate($status) {
	global $PIVOTX;
	
	$mailtemplates = array(
		'other_return_tpl' => 'shop_email_other_return_tpl'
	);
	
	// and a make everything change for the confirmation mail
	// pass by reference dudes 
    $PIVOTX['extensions']->executeHook('shop_return_mail', $mailtemplates);
	
	//debug('get mail template for '.$status .' so '. $mailtemplates[$status]);

	$templatename = dirname(dirname(__FILE__)) .'/'. $PIVOTX['config']->get($mailtemplates[$status]);
	//debug('attempting '.  $templatename . ' ...');
	if(file_exists($templatename)) {
		//debug( $templatename . ' exists');
		return $templatename;
	} else {
		// turn the shop off
		$PIVOTX['config']->set('shop_enabled', false);
        $logmessage = $PIVOTX['config']->get('shop_last_errors');
        $logmessage .= '|Mail template fallback triggered';
        $PIVOTX['config']->set('shop_last_errors', $logmessage);  
		return dirname(dirname(__FILE__)).'/templates/email_order_debug.tpl';
	}
}

/**
 * Send a mail to the customer depending on the current status
 */
function _shop_order_mail_default($status, $order) {
	global $PIVOTX;
	//debug('get template for status: ' . $status);
	
	
	$templatename = _shop_get_mailtemplate($status);

	//debug('get template: ' . $templatename);
	if(class_exists('pivotmail')) {
		$template = pivotmail::read_mail_template($templatename);
		$macros = array();
		
		// make the shop the sender
		$macros['[[from_name]]'] = $PIVOTX['config']->get('shop_email_name');
		$macros['[[from_email]]'] = $PIVOTX['config']->get('shop_email_address');

		pivotmail::set_sender($macros['[[from_name]]'], $macros['[[from_email]]']);

		// order specific macros
		$macros['[[status]]'] = $status;
		$macros['[[sitename]]'] = $PIVOTX['config']->get('sitename');
		
		foreach($order as $label => $value) {
			$macros['[['.$label.']]'] = $value;
		}
		
		// site specific macros
		foreach($PIVOTX['paths'] as $label => $value) {
			$macros['[['.$label.']]'] = $value;
		}
		$macros['[[order_summary]]'] = _shop_order_summary($order);

		//$template = strtr($template, $macros);

		if(stristr($template, '<body>')) {
			$htmlmailheader = "Content-Type: text/html; charset=utf-8";
			pivotmail::add_header($htmlmailheader);
			$htmlmailheader = "Content-Transfer-Encoding: quoted-printable";
			pivotmail::add_header($htmlmailheader);
		}
		
		// always bcc shop owner
		$bccowner = true;

		$order['mail_result'] = pivotmail::mail($macros['[[user_name]]'], $macros['[[user_email]]'], $template, $macros, $bccowner);
		
	} else {
		$order['mail_result'] = 'pivotmail class is missing';
	}
	return $order;
}

/**
 * Checks if the text is a valid email address.
 *
 * Given a chain it returns true if $theAdr conforms to RFC 2822.
 * It does not check the existence of the address.
 * Suppose a mail of the form
 *  <pre>
 *  addr-spec     = local-part "@" domain
 *  local-part    = dot-atom / quoted-string / obs-local-part
 *  dot-atom      = [CFWS] dot-atom-text [CFWS]
 *  dot-atom-text = 1*atext *("." 1*atext)
 *  atext         = ALPHA / DIGIT /    ; Any character except controls,
 *        "!" / "#" / "$" / "%" /      ;  SP, and specials.
 *        "&" / "'" / "*" / "+" /      ;  Used for atoms
 *        "-" / "/" / "=" / "?" /
 *        "^" / "_" / "`" / "{" /
 *        "|" / "}" / "~" / "." /
 * </pre>
 *
 * @param string $theAdr
 * @return boolean
 */
function _shop_isEmail( $theAdr ) {

	// Use an existing "isemail" function if it exists 
	// (as in PivotX) to ensure consistent results.
	if (function_exists('isemail')) {
		return isEmail($theAdr );
	}

	// default
	$result = FALSE;

	// go ahead
	if(( ''!=$theAdr )||( is_string( $theAdr ))) {
		$mail_array = explode( '@',$theAdr );
	}
	if( !is_array( $mail_array )) { return FALSE; }
	if( 2 == count( $mail_array )) {
		$localpart = $mail_array[0];
		$domain_array  = explode( '.',$mail_array[1] );
	} else {
		return FALSE;
	}
	if( !is_array( $domain_array ))  { return FALSE; }
	if( 1 == count( $domain_array )) { return FALSE; }

	/* relevant info:
	 $localpart contains atext (local part of address)
	 $domain_array  contains domain parts of address
		and last one must be at least 2 chars 
	 */
	$domain_toplevel = array_pop( $domain_array );
	if(is_string($domain_toplevel) && (strlen($domain_toplevel) > 1)) {
		// put back
		$domain_array[] = $domain_toplevel;
		$domain = implode( '',$domain_array );
		// now we have two string to test
		// $domain and $localpart
		$domain        = preg_replace( "/[a-z0-9]/i","",$domain );
		$domain        = preg_replace( "/[-|\_]/","",$domain );
		$localpart = preg_replace( "/[a-z0-9]/i","",$localpart);
		$localpart = preg_replace(
			"#[-.|\!|\#|\$|\%|\&|\'|\*|\+|\/|\=|\? |\^|\_|\`|\{|\||\}|\~]#","",$localpart);
		// If there are no characters left in localpart or domain, the
		// email address is valid.
		if(( '' == $domain )&&( '' == $localpart )) { $result = TRUE; }
	}
	return $result;
}