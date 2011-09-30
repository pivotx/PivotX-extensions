<?php
/**
 * Register 'addtocart' as a smarty tag.
 *
 * Used when the body is not shown
 * 
 * An example: 
[[addtocart
	class="button"
	entryid=$entry.uid
	entry=$entry
	showqty=1
	showlabels=1
	text="&quot;Buy This!&quot;"]]
 *
 **/
$PIVOTX['template']->register_function('addtocart', 'smarty_addtocart');

/**
 * addtocart
 *
 * @param array $params
 * @param object $smarty
 * @return unknown
 */
function smarty_addtocart($params, &$smarty) {
    global $PIVOTX;

	if($PIVOTX['config']->get('shop_enabled')!=false) {
		$params = cleanParams($params);
		$vars = $smarty->get_template_vars();
		
		if($params['entryid'] && $params['type']=='entry') {
			$entry = $PIVOTX['db']->read_entry($params['entryid']);
		} elseif($params['entry']) {
			$entry = $params['entry'];
		} else {
			print "<!-- entry or entryid missing -->";
			return;
		}
		
		if($entry['extrafields']['item_is_available']=='yes') {
			if(isset($params['showqty']) && $params['showqty']>0) {
				$params['no_items'] = $params['showqty'];
				$params['action'] = 'add';			
			}
			print _shop_buybutton($entry, $params);
		} elseif($entry['extrafields']['item_is_available']=='no') {
			print _shop_soldoutbutton($entry, $params);
		} else {
			print '<!-- product availability unknown -->';
		}
	} else {
		print '<!-- shop is disabled -->';
	}
}

$PIVOTX['template']->register_function('shoppingcart', 'smarty_shoppingcart');

/**
 * shoppingcart
 *
 * @param array $params
 * @param object $smarty
 * @return unknown
 */
function smarty_shoppingcart($params, &$smarty) {
    global $PIVOTX;

	if($params['hideempty']!=false) {
		$cart = $PIVOTX['shoppingcart'];

		if(is_array($cart) && count($cart['items'])>0) {
			$cartnotempty = true;
		} else {
			$cartnotempty = false;
		}
	} else {
		$cartnotempty = true;
	}
	if($PIVOTX['config']->get('shop_enabled')!=false) {
		if($cartnotempty) {
			if($params['type']) {
				$output = _shop_show_cart($params['type']);
			} else {
				$output = _shop_show_cart('compact');
			}
		} else {
			$output = '<!-- cart is empty and hidden -->';
		}
	} else {
		$output = '<!-- shop is disabled -->';
	}
	
    return '<div class="shoppingcartcontainer">'.$output.'</div>';
}

/**
 * Register 'pricedisplay' as a smarty tag.
 *
 * Used when the body is not shown
 * 
 * An example: 
[[pricedisplay
	entryid=$entry.uid
	entry=$entry]]
 *
 **/
$PIVOTX['template']->register_function('pricedisplay', 'smarty_pricedisplay');

/**
 * pricedisplay
 *
 * @param array $params
 * @param object $smarty
 * @return unknown
 */
function smarty_pricedisplay($params, &$smarty) {
    global $PIVOTX;

	if($PIVOTX['config']->get('shop_enabled')!=false) {

		$params = cleanParams($params);
		$vars = $smarty->get_template_vars();
		
		if($params['entryid'] && $params['type']=='entry') {
			$entry = $PIVOTX['db']->read_entry($params['entryid']);
		} elseif($params['entry']) {
			$entry = $params['entry'];
		} else {
			print "<!-- entry or entryid missing -->";
			return;
		}
		
		if($entry['extrafields']['item_is_available']=='yes') {
			print _shop_pricedisplay($entry);
		} elseif($entry['extrafields']['item_is_available']=='no') {
			print _shop_pricedisplay($entry);
		} else {
			print '<!-- product price unknown -->';
		}
	} else {
		print '<!-- shop is disabled -->';
	}
}