<?php
/**
 * The file that manages the OOPs integration
 */

/**
 * Set the primary key to something other than the automagical
 */
oops_registry::instance()->set_setting('oops.order.primarykey_fieldname','order_id');

/**
 * Handle the listing and export of transactions
 *
pivotx_order

Field	Type	Null	Key	Default	Extra
order_id	int(10)	NO	PRI	NULL	auto_increment
order_public_code	mediumtext	NO	 	NULL	 
order_public_hash	mediumtext	NO	 	NULL	 
order_status	enum('unknown','initialized','waiting','complete','error','cancelled','refunded','saved','expired','junk')	YES	 	unknown	 
order_datetime	timestamp	NO	 	CURRENT_TIMESTAMP	on update CURRENT_TIMESTAMP
user_name	text	YES	 	NULL	 
user_email	text	NO	MUL	NULL	 
user_phone	text	YES	 	NULL	 
user_address	text	YES	 	NULL	 
user_postcode	tinytext	NO	 	NULL	 
user_city	tinytext	NO	 	NULL	 
user_country	tinytext	NO	 	NULL	 
user_ip	mediumtext	NO	 	NULL	 
user_hostname	mediumtext	NO	 	NULL	 
user_browser	mediumtext	NO	 	NULL	 
shipping_handler	text	YES	 	NULL	 
shipping_external_code	text	YES	 	NULL	 
shipping_status	text	YES	 	NULL	 
shipping_datetime	timestamp	YES	 	NULL	 
payment_provider	text	YES	 	NULL	 
payment_external_code	text	YES	 	NULL	 
payment_status	text	YES	 	NULL	 
payment_datetime	timestamp	YES	 	NULL	 
payment_amount_total	text	YES	 	NULL	 
payment_message	text	YES	 	NULL

pivotx_order_item

Field	Type	Null	Key	Default	Extra
order_item_id	int(10)	NO	PRI	NULL	auto_increment
order_id	int(10)	NO	 	NULL	 
order_datetime	timestamp	NO	 	CURRENT_TIMESTAMP	on update CURRENT_TIMESTAMP
order_no_items	text	YES	 	NULL	 
item_id	int(10)	NO	 	NULL	 
item_option	text	YES	 	NULL	 
item_code	text	YES	 	NULL	 
item_title	text	NO	 	NULL	 
item_content	text	YES	 	NULL	 
item_price	text	YES	 	NULL	 
item_price_incl_tax	text	YES	 	NULL	 
item_tax_percentage	text	YES	 	NULL	 
 *
 */
function pageShopTransactions() {
    global $PIVOTX;
    
    if($_POST) {
        if(isset($_POST['shop_action']) && in_array($_POST['shop_action'], array('wait','complete','error','cancel','refund','save','expire','junk'))) {
            $order = new ShopCart('order', $_POST['order_id']);
            $order_details = $order->getOrderDetails();
            //enum('unknown','initialized','waiting','complete','error','cancelled','refunded','saved','expired','junk')
            switch($_POST['shop_action']) {
                case 'wait':
                    $order_details['order_status'] = 'waiting';
                    break;
                case 'complete':
                    $order_details['order_status'] = 'complete';
                    break;
                case 'cancel':
                    $order_details['order_status'] = 'cancelled';
                    break;
                case 'refund':
                    $order_details['order_status'] = 'refunded';
                    break;
                case 'save':
                    $order_details['order_status'] = 'saved';
                    break;
                case 'expire':
                    $order_details['order_status'] = 'expired';
                    break;
                case 'junk':
                    $order_details['order_status'] = 'junk';
                    break;
            }
            // save userdata and create order
            $order_details = _shop_save_order($order_details);
            $order->resetCart();
        }
        if(isset($_POST['shop_shipping_action']) && in_array($_POST['shop_shipping_action'], array('ship'))) {
            $order = new ShopCart('order', $_POST['order_id']);
            $order_details = $order->getOrderDetails();
            switch($_POST['shop_shipping_action']) {
                case 'ship':
                    $order_details['shipping_datetime'] = date('Y-m-d H:i:s');
                    $order_details['shipping_status'] = 'Success';
                    break;
            }
            // save userdata and create order
            $order_details = _shop_save_order($order_details);
            $order->resetCart();
        }
        if(isset($_POST['shop_payment_action']) && in_array($_POST['shop_payment_action'], array('complete', 'refund', 'cancel'))) {
            $order = new ShopCart('order', $_POST['order_id']);
            $order_details = $order->getOrderDetails();
            switch($_POST['shop_payment_action']) {
                case 'complete':
                    $order_details['payment_datetime'] = date('Y-m-d H:i:s');
                    $order_details['payment_status'] = 'Success';
                    $order_details['order_status'] = 'complete';
                    $order_details['payment_amount_total'] = $order_details['totals']['cumulative_incl_tax'];
                    break;
                case 'refund':
                    $order_details['payment_datetime'] = date('Y-m-d H:i:s');
                    $order_details['payment_status'] = 'Refunded';
                    $order_details['order_status'] = 'refunded';
                    break;
                case 'cancel':
                    $order_details['payment_datetime'] = date('Y-m-d H:i:s');
                    $order_details['payment_status'] = 'Cancelled';
                    $order_details['order_status'] = 'cancelled';
                    break;
            }
            // save userdata and create order
            $order_details = _shop_save_order($order_details);
            $order->resetCart();
        }
    }

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_ADMIN);

    $PIVOTX['template']->assign('title', __('Shop Transactions'));

    $te = new oops_tableedit('order');
    $te->set_base_href('?page=shoptransactions');
    $te->set_columns(array( 'order_id', 'user_name', 'orderitems', 'order_status', 'shipping_handler', 'payment_provider', '&#160;' ));

    $te->set_default_order('!order_id');
    //$te->set_non_editable(array('order_datetime', 'user_ip', 'user_hostname'));
    $te->set_sortable(array('order_id', 'user_name', 'user_email'));
    $te->set_searchable(array('order_id', 'user_name', 'user_email', 'order_public_code'));

    $output .= $te->output_table(false, true);
    
    $output .= '';
    $output .= str_replace('[[extensionurl]]', $PIVOTX['paths']['extensions_url'], '
    <script type="text/javascript" src="[[extensionurl]]shop/js/admin.shop.js"></script>');
    $output .= str_replace('[[extensionurl]]', $PIVOTX['paths']['extensions_url'], '
    <style type="text/css">
        @import url([[extensionurl]]shop/css/admin.shop.css);
    </style>');


    $PIVOTX['template']->assign('html',$output);

    renderTemplate('generic.tpl');
}


/**
 * Custom stuff with the orders (like loading entries)
 *
 * TODO: make payment and shipping hooks for back-end
 */
class shop_order extends oops_order
/*{{{*/
{
    /**
     * Get items associated with an order
     *
     * Example: <code>[[$order->getOrderItems()]]</code>
     */
    public function getOrderItems() {
        $_order_id = $this->order_id;
        $item = new oops_order_item();
        $this->items = $item->loadallby('order_id', $_order_id);
    }
    
    /**
     * @nosmarty
     */
    public function tableedit_value_callback($col, $href = false) {
        $attr  = '';
        $value = $this->$col;

        // haal de hele order even op
        if(!isset($this->order)) {
            $this->order = new ShopCart('order', $this->order_id);
            $this->order->setDisplay('email');
            $this->shipping = $this->order->renderShipping();
            $this->discounts = $this->order->renderDiscounts();
            $this->totals = $this->order->renderTotals();
            $this->cart = $this->order->renderCart();
            //debug_printr($this->order);
            $this->customer = _shop_order_user($this->order->getOrderDetails());
        }

        switch ($col) {
            case 'order_id':
  
                $value = "<b>&#8470; ". $value ."</b> ";
                $value .= '<span class="date orderdate">'. date("'y-m-d H:i", strtotime($this->order_datetime)) .'</span> ';
                $value .= '<br /><small title="public order code">pc-'. $this->order_public_code .'</small>';
                break;
            case 'user_name':
                $value = '<div>';
                $value .= '<b><a href="mailto:'.$this->user_email.'">'. $this->user_name .'</a></b><br />';
                $value .= $this->user_address.'<br />'.$this->user_postcode.' '.$this->user_city;
                $value .= '</div>';
                $value .= '<div id="customer-'.$this->order_id.'" class="hidden popup customeraddress">'.$this->customer .'</div> ';
                $attr = ' class="username" ';
                break;
            case 'orderitems':
                $this->getOrderItems();
                foreach($this->items as $item) {
                    $options[] = ' '.$item->item_title;
                }
                $value = join(',',$options);
                $value .= '<div id="cart-'.$this->order_id.'" class="hidden popup shoppingcart">'. $this->cart .'</div> ';
                break;
            
            case 'order_status':
                $value = '<span class="'. $this->order_status .'">'. $this->order_status .'</span> ';

            //enum('unknown','initialized','waiting','complete','error','cancelled','refunded','saved','expired','junk')
                switch($this->order_status) {
                    case 'unknown':
                    case 'initialized':
                    case 'refunded':
                    case 'saved':
                    case 'complete':
                        $value .= '- nothing to do.';
                        break;
                    case 'waiting':
                        $value .= '<form method="post">';
                        $value .= '<input type="hidden" name="order_id" value="'.$this->order_id.'">';
                        $value .= '<div class="buttons buttons_small"><button name="shop_action" value="complete" class="positive"><span>'.st('Complete order').'</span></button></div>';
                        $value .= '<div class="buttons buttons_small"><button name="shop_action" value="cancel" class="negative"><span>'.st('Cancel order').'</span></button></div>';
                        $value .= '</form> ';
                        break;
                    case 'expired':
                    case 'error':
                        $value .= '<form method="post">';
                        $value .= '<input type="hidden" name="order_id" value="'.$this->order_id.'">';
                        $value .= '<div class="buttons buttons_small"><button name="shop_action" value="junk" class="negative"><span>'.st('Junk order').'</span></button></div>';
                        $value .= '</form> ';
                        break;
                    case 'cancelled':
                        $value .= '<form method="post">';
                        $value .= '<input type="hidden" name="order_id" value="'.$this->order_id.'">';
                        $value .= '<div class="buttons buttons_small"><button name="shop_action" value="junk" class="negative"><span>'.st('Junk order').'</span></button></div>';
                        $value .= '</form> ';
                        break;
                    case 'junk':
                        $value .= '- nothing to do - will clean up automatically.';
                        break;
                    default:
                        break;
                }
                break;
            case 'shipping_handler':
                $value = "<b>". $value ."</b> ";
                if($this->shipping_datetime!=0) {
                    $value .= '<span class="date shippingdate">'. date("'y-m-d H:i", strtotime($this->shipping_datetime)) .'</span> ';
                }
                $value .= "<br /> ";
                switch($this->shipping_status) {
                    case 'Success':
                        $value .= '<span class="positive">'. st('Order has been shipped') .'</span> ';
                        break;
                    case 'Failure':
                        $value .= '<form method="post">';
                        $value .= '<input type="hidden" name="order_id" value="'.$this->order_id.'">';
                        $value .= '<div class="buttons buttons_small"><button name="shop_action" value="cancel" class="negative"><span>'.st('Cancel order').'</span></button></div>';
                        $value .= '</form> ';
                        break;
                    default:
                        if(!in_array($this->order_status, array('unknown','initialized','error','cancelled','refunded','expired','junk'))) {
                            $value .= '<form method="post">';
                            $value .= '<input type="hidden" name="order_id" value="'.$this->order_id.'">';
                            $value .= '<div class="buttons buttons_small"><button name="shop_shipping_action" value="ship" class="positive"><span>'.st('Ship order').'</span></button></div>';
                            $value .= '</form> ';
                        }
                        break;
                }
                break;
            case 'payment_provider':
                $value = "<b>". $value ."</b> ";
                if($this->payment_datetime!=0) {
                    $value .= '<span class="date paymentdate">'. date("'y-m-d H:i", strtotime($this->payment_datetime)) .'</span> ';
                }
                $value .= "<br /> ";
                $value .= "<small>". st('Total') .' '. ShopCart::renderPrice($this->payment_amount_total) .'</small> ';
                //$value .= '<span class="'. $this->payment_status .'">'. $this->payment_status .'</span> ';
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
                switch($this->payment_status) {
                    case 'Success':
                        $value .= '<span class="positive"><b>'. $this->payment_status .'</b></span> ';
                        $value .= '<form method="post">';
                        $value .= '<input type="hidden" name="order_id" value="'.$this->order_id.'">';
                        $value .= '<div class="buttons buttons_small"><button name="shop_payment_action" value="refund" class="negative"><span>'.st('Refund payment').'</span></button></div>';
                        $value .= '</form> ';
                        break;
                    case 'Cancelled':
                    case 'Failure':
                    case 'Expired':
                        $value .= '<span class="negative"><b>'. $this->payment_status .'</b></span> ';
                        $value .= '<form method="post">';
                        $value .= '<input type="hidden" name="order_id" value="'.$this->order_id.'">';
                        $value .= '<div class="buttons buttons_small"><button name="shop_payment_action" value="cancel" class="negative"><span>'.st('Cancel payment').'</span></button></div>';
                        $value .= '</form> ';
                        break;
                    default:
                        $value .= '<span class="neutral"><b>'. $this->payment_status .'</b></span> ';
                        if(!in_array($this->order_status, array('unknown','initialized','error','cancelled','refunded','expired','junk'))) {
                            $value .= '<form method="post">';
                            $value .= '<input type="hidden" name="order_id" value="'.$this->order_id.'">';
                            $value .= '<div class="buttons buttons_small"><button name="shop_payment_action" value="complete" class="positive"><span>'.st('Complete payment').'</span></button></div>';
                            $value .= '</form> ';
                        }
                        break;
                        break;
                }
                break;
        }

        return array($attr, $value);
    }

    /**
     * @nosmarty
     */
    public function tableedit_input_callback($col) {
        $changes = array();

        switch ($col) {
            case 'user_name':
            case 'user_email':
            case 'user_phone':
            case 'user_address':
            case 'user_postcode':
            case 'user_city':
            case 'user_country':
                $changes['type'] = 'text';
                break;

            case 'orderitems':
                $changes['type'] = 'select';
                $this->getOrderItems();
                //debug_printr($this->orderitems);
                foreach($this->items as $item) {
                    $options[$item->item_id] = $item->item_title;
                }
                
                $changes['options'] = $options;
                break;
            
            default:
                $changes['type'] = 'output';
                break;
        }

        return $changes;
    }


}
/*}}}*/

/**
 * Custom stuff with the order_items (like loading entries)
 */
class shop_order_item extends oops_order_item
/*{{{*/
{
    
}
/*}}}*/

/**
 * Custom stuff with the order_items (like loading entries)
 */
class shop_order_log extends oops_order_log
/*{{{*/
{
    
}
/*}}}*/