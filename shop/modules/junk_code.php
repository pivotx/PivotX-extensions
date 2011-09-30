<?php
		$output = '';

		if($this->display == 'full') {
			$output .= '<table class="shoppingcartitems">';
			foreach($cart['items'] as $key => $no_items) {
				if(stristr($key, "::")) {
					list($key, $key_option) = explode("::", $key);
				} else {
					$key_option = false;
				}

				$entries[$key] = $PIVOTX['db']->read_entry($key);

				//debug_printr($entries[$key]);
				if($key_option) {
					$entries[$key]['item_product_options'] = _shop_load_entry_options($entries[$key]);
					$entries[$key]['option_key'] = $key_option;
					$entries[$key]['option_label'] = $entries[$key]['item_product_options'][$key_option];
				
					$option_label = ' <span class="option">'.$entries[$key]['option_label'].'</span>';

				} else {
					$option_label = '';
				}

				$oddclass = !($oddclass)?'odd':'';
				$output .= '<tr class="'.$oddclass.'">';
				$output .= '<td class="item"><h4><a href="'. $entries[$key]['link'] .'" class="itemlink">'. $entries[$key]['title'] .'</a>' . $option_label . '</h4></td>';

				$output .= '<td class="singleprice">'.$entries[$key]['pricedisplay'].'</td>';

				$params['no_items'] = $no_items;
				$params['text'] = st('Update');
				$params['class'] = 'update';
				$params['action'] = 'update';

				$params['option_key'] = $entries[$key]['option_key'];
				$params['option_label'] = $entries[$key]['option_label'];

				$output .= '<td>'.st('&times;').'</td>';
				
				$output .= '<td class="updatelink">'._shop_buythisbutton($entries[$key], $params).'</td>';

				$entries[$key]['removelink'] = _shop_removelink($entries[$key]);
				$output .= '<td class="removelink"><a href="'. $entries[$key]['removelink'] .'" class="removelink">'. st("Remove") .'</a></td>';

				$totaldisplay = _shop_pricetotaldisplay($entries[$key], $no_items);
				
				$output .= '<td class="totalprice">'.$totaldisplay.'</td>';
				

				$output .= "</tr>\n";
				$num += $no_items;
			}
			$output .= '<tr class="totals itemtotal"><td colspan="6"><em>'.$num.'</em> '.st('items in cart').'.</td></tr>';

			if($PIVOTX['config']->get('shop_use_shipping')==true) {
				$output .= '<tr class="totals shippingcosts"><td colspan="3"></td><td>'.st('Shipping costs').'</td><td colspan="4">'.$cart['shipping']['display']['full'].'</td></tr>';
			}

			$totals = _shop_cart_total_amounts($cart, $entries);
			$output .= '<tr class="totals odd taxtotals"><td colspan="3"></td><td><div class="total_label">'.st('Total').'</div></td><td colspan="2">'.$totals['display_amount_full'].'</td></tr>';

			$output .= '<tr class="action"><td colspan="6"><!--<a href="'. $viewlink .'">'.st("Refresh cart").'</a>-->';
			$checkout['text'] = st('Checkout');
			$checkout['class'] = 'checkout';
			$checkout['action'] = 'checkout';
			$output .= _shop_checkoutbutton($checkout);
			$output .= '</td></tr>';
			
			$output .= '</table>';

	
		    $return_url = $PIVOTX['config']->get('shop_default_homepage', '/index.php?w=shop');
			$output .= '<p><a href="'.$return_url.'" class="pivotx-more-link">'.st("Continue shopping").'</a></p>';
		} elseif($this->display == 'compact') {
			$output .= "<h4>".st("Shopping cart")."</h4>";

			$output .= '<ul>';
			foreach($cart['items'] as $key => $no_items) {

				if(stristr($key, "::")) {
					list($key, $key_option) = explode("::", $key);
				} else {
					$key_option = false;
				}

				$entries[$key] = $PIVOTX['db']->read_entry($key);

				if($key_option) {
					$entries[$key]['item_product_options'] = _shop_load_entry_options($entries[$key]);
					$entries[$key]['option_key'] = $key_option;
					$entries[$key]['option_label'] = $entries[$key]['item_product_options'][$key_option];
				
					$option_label = ' <span class="option">'.$entries[$key]['option_label'].'</span>';

				} else {
					$option_label = '';
				}

				$output .= '<li><span class="no-items"><strong>'.$no_items.' </strong><span>'.st('&times;').'</span></span> <a href="'. $entries[$key]['link'] .'" class="itemlink">'. $entries[$key]['title'] . $option_label .'</a></li>';
				$num += $no_items;
			}
			$output .= '</ul>';
			$output .= '<p class="totals itemtotal"><em>'.$num.'</em> '.st('items in cart').'.</p>';
			$totals = _shop_cart_total_amounts($cart, $entries);
			$output .= $totals['display_amount_compact'];

			$output .= '<p><a href="'. $viewlink .'">'.st("View cart").'</a></p>';
		} elseif($this->display == 'checkout') {
			//$output .= "<h4>".st("Shopping cart")."</h4>";

			$output .= '<table class="shoppingcartitems">';
			foreach($cart['items'] as $key => $no_items) {
				if(stristr($key, "::")) {
					list($key, $key_option) = explode("::", $key);
				} else {
					$key_option = false;
				}

				$entries[$key] = $PIVOTX['db']->read_entry($key);

				if($key_option) {
					$entries[$key]['item_product_options'] = _shop_load_entry_options($entries[$key]);
					$entries[$key]['option_key'] = $key_option;
					$entries[$key]['option_label'] = $entries[$key]['item_product_options'][$key_option];
				
					$option_label = ' <span class="option">'.$entries[$key]['option_label'].'</span>';

				} else {
					$option_label = '';
				}
				$oddclass = !($oddclass)?'odd':'';
				$output .= '<tr class="'.$oddclass.'">';
				$output .= '<td><span>'. $entries[$key]['title'] . $key_option. $option_label .'</span></td>';
				$output .= '<td class="singleprice">'.$entries[$key]['pricedisplay'].'</td>';
				$output .= '<td>'.st('&times;').'</td>';
				$output .= '<td><strong>'.$no_items.' </strong></td>';
				$output .= '<td>=</td>';
				$totaldisplay = _shop_pricetotaldisplay($entries[$key], $no_items);
				$output .= '<td class="totalprice">'.$totaldisplay.'</td>';
				$num += $no_items;
				$output .= '</tr>';
			}
			
			$output .= '<tr class="totals itemtotal"><td colspan="6"><em>'.$num.'</em> '.st('items in cart').'.</td></tr>';

			if($PIVOTX['config']->get('shop_use_shipping')==true) {
				$output .= '<tr class="totals shippingcosts"><td colspan="1"></td><td>'.st('Shipping costs').'</td><td colspan="4">'.$cart['shipping']['display']['full'].'</td></tr>';
			}

			$totals = _shop_cart_total_amounts($cart, $entries);
			$output .= '<tr class="totals odd taxtotals"><td colspan="1"></td><td><div class="total_label">'.st('Total').'</div></td><td colspan="4">'.$totals['display_amount_checkout'].'</td></tr>';
			$output .= "</table>";

		} else {
			$output .= "<h4>".st("Shopping cart")."</h4>";

			foreach($cart['items'] as $key => $no_items) {
				$num += $no_items;
			}
			$output .= '<p class="totals"><em>'.$num.'</em> '.st('items in cart').'.</p>';
			$totals = _shop_cart_total_amounts($cart, $entries);
			$output .= $totals['display_amount_minimal'];
			$output .= '<p><a href="'. $viewlink .'">'.st("View cart").'</a></p>';
		}
		$cartoutput = '<div class="shoppingcart shoppingcart-'.$size.'">'.$output.'</div>';