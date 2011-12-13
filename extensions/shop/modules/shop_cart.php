<?php
/**
 * Functions for manipulating the shopping cart
 */

class ShopCart {
    
    protected $type = 'cart'; // cart|order
    protected $display = 'normal';
    protected $items = false;
    protected $shipping = false;
    protected $templates = false;
    protected $discounts = false;
    protected $totals = false;
    protected $user = false;
    protected $payment = false;
    protected $order = false;
    protected $showvatincart = false;

    /**
     * Initialisation. Create an empty cart or load an existing
     *
     * @return ShopCart
     */
    public function __construct($type='cart', $order_id=null) {
        
        $this->type = $type;
        if($this->type=='cart') {
            $this->loadCart();
        } else {
            $this->loadOrder($order_id);
        }

    }
    
    /**
     * Load current cart from session and set basic vars
     */
    public function loadCart() {
        // get cart
        //$this->resetCart();
        $cart = $this->getSessionVar('cart');
        
        //debug_printr($cart);
        // set current values
        if(!empty($cart)) {
            foreach(array('items', 'shipping', 'discounts', 'totals', 'user', 'payment') as $key) {
                if(isset($cart[$key])) {
                    $this->$key = $cart[$key];
                }
            }
        }
        //debug('loaded cart');
        //debug_printr($this);
    }
    
    public function notEmpty() {
        if(is_array($this->items) && count($this->items)>0) {
            return true;
        }
        return false;
    }
    
    public function getOrderId() {
        if($this->order) {
            $order_id = $this->order['order_id'];
            if($order_id) {
                return $order_id;
            }
        }
        // no order and no order id
        return false;
    }
    
    public function getOrderDetails() {
        if($this->order) {
            $order_details = $this->order;
            if($order_details) {
                return $order_details;
            }
        }
        // no order and no order id
        return false;
    }
    
    public function getOrderTotals() {
        if($this->order) {
            $order_totals = $this->order['totals'];
            if($order_totals) {
                return $order_totals;
            }
        }
        // no order and no order id
        return false;
    }
    
    public function getPaymentProvider() {
        if($this->order) {
            $payment_provider = $this->order['payment_provider'];
            if($payment_provider) {
                return $payment_provider;
            }
        }
        // no order and no order id
        return false;
    }
    
    /**
     * Load current cart from session and set basic vars
     *
     * This uses the ShopSql class from shop_sql.php
     *
     * TODO: yuck this is ugly
     */
    public function loadOrder($order_id=null) {
        global $PIVOTX;

        if(!$PIVOTX['order'] || ($PIVOTX['order'] && $PIVOTX['order']->getOrderId()!=$order_id)) {
            $ordersql = new ShopSql();
            if($order_id) {
                $order = $ordersql->getOrder($order_id);
            } elseif($PIVOTX['session']->getValue('order_id')) {
                // attempt to load it by session
                $order = $ordersql->getOrder($PIVOTX['session']->getValue('order_id'));
            } else {
                // attempt to load it by session
                $order_code = $PIVOTX['session']->getValue('order_public_code');
                $order_hash = $PIVOTX['session']->getValue('order_public_hash');
                $order = $ordersql->getOrderByCode($order_code, $order_hash);
            }
            //debug('loaded order');
            //debug_printr($order);
            //debug('end of loaded order');
    
            if(!empty($order)) {
                $this->order = $order;
                // rebuild the cart items with extra data
                foreach($order['order_items'] as $item_id => $item) {
                    $this->items[$item['item_id']][$item['item_option']] = $item;
                    $this->items[$item['item_id']][$item['item_option']]['amount'] = $item['order_no_items'];
                }

                // calculate shipping on non empty orders
                if($PIVOTX['config']->get('shop_use_shipping')==true) {
                    //$cart = _shop_total_shipping($cart);
                    $this->calculateShipping();
                    $this->order['shipping'] = $this->shipping;
                }
                // calculate discounts on non empty orders
                if($PIVOTX['config']->get('shop_use_discounts')==true) {
                    $this->calculateDiscounts();
                    $this->order['discounts'] = $this->discounts;
                }
                $this->calculateTotals();
                $this->order['totals'] = $this->totals;
            } else {
                //debug('could not load order');
            }
    
            //debug('returned (fresh) order');
            //debug_printr($this);
            //debug('end of returned order');
            return $this;
        } else {
            //debug('returned (cached) order');
            return $PIVOTX['order'];
        }
    }
    
    /**
     * Store the current cart to session
     */
    public function storeCart() {
        // load cart
        //$cart = $this->getSessionVar('cart');
        //debug('stored cart loaded');
        //debug_printr($cart);
        
        // overwrite cart with current settings
        foreach(array('items', 'shipping', 'discounts', 'totals', 'user', 'payment') as $key) {
            if(isset($this->$key)) {
                $cart[$key] = $this->$key;
            }
        }
        // save cart
        $this->setSessionVar('cart', $cart);   
        //debug('stored cart object');
        //debug_printr($this);
    }

    /**
     * Remove a cart, and build a new one
     */
    public function resetCart() {

        //debug('reset the shopping cart');
        foreach(array('items', 'shipping', 'discounts', 'totals', 'user', 'payment') as $key) {
            $this->$key = false;
        }

        $this->setSessionVar('cart', null);     

        //debug_printr(array('SESSION'=>$_SESSION, 'PIVOTX-session'=>$PIVOTX['session'], 'cart'=>$this));
        //debug('the shopping cart was reset');
    }
    
    /**
     * Add an item to the current cart
     */
    public function addItem($item_id, $amount, $item_variant='default') {

        // only update this variant
        if(
            is_array($this->items[$item_id])
            && is_array($this->items[$item_id][$item_variant])
            && array_key_exists('amount', $this->items[$item_id][$item_variant])
        ) {
            //debug('increase amount');
            // add amount
            $this->items[$item_id][$item_variant]['amount'] += $amount;
        } else {
            //debug('add item');
            $_cart_item = $this->items[$item_id];
            
            if(!$_cart_item) {
                $_cart_item = array();
            }
            
            // add admount to variant
            $_cart_item[$item_variant]['amount'] = $amount;
            
            // set new amount
            $this->items[$item_id][$item_variant] = $_cart_item[$item_variant];
        }

        
        $this->storeCart();
        //debug('add item');
        //debug_printr($this);
    }
    
    /**
     * Change the amount of an item
     */
    public function updateItem($item_id, $amount, $item_variant='default') {
        $_cart_item = $this->items[$item_id];
        
        if(!$_cart_item) {
            $_cart_item = array();
        }
        
        // replace amount of variant
        $_cart_item[$item_variant]['amount'] = $amount;
        
        // only update this variant
        $this->items[$item_id][$item_variant] = $_cart_item[$item_variant];

        $this->storeCart();
        //debug('update item');
        //debug_printr($this);
    }
    
    /**
     * Remove an item
     */
    public function deleteItem($item_id, $item_variant='default') {

        //debug('delete item from cart: '. $item_id .' - '. $item_variant);

        if(!empty($item_variant) && $item_variant!='default') {
            // only remove this variant            
            //debug('delete item variant from cart: '. $item_id .' - '. $item_variant);
            unset($this->items[$item_id][$item_variant]);
        } else {
            // item variant 0 is all... hopefully
            //debug('delete all of item from cart: '. $item_id);
            unset($this->items[$item_id]);
        }

        $this->storeCart();
        //debug_printr($this);
    }

    /**
     * Return only the 'product' items we want to render on the user screen
     *
     * Mostly strips products that fall in the shop_extrascategory.
     */
    protected function filterItemsProductsOnly()
    {
        global $PIVOTX;

        $items = array();

        $shop_extrascategory = $PIVOTX['config']->get('shop_extrascategory', 'shop');
        if ($shop_extrascategory == '') {
            $items = $this->items;
        }
        else {
            foreach($this->items as $item_id => $item_items) {
                $entryitem = $PIVOTX['db']->read_entry($item_id);

                if (!in_array($shop_extrascategory,$entryitem['category'])) {
                    $items[$item_id] = $item_items;
                }
            }
        }

        return $items;
    }

    /**
     * Return only the 'extra' items we want to render on the user screen
     *
     * Mostly leaves the products that fall in the shop_extrascategory.
     */
    protected function filterItemsExtrasOnly()
    {
        global $PIVOTX;

        $items = array();

        $shop_extrascategory = $PIVOTX['config']->get('shop_extrascategory', 'shop');
        if ($shop_extrascategory != '') {
            foreach($this->items as $item_id => $item_items) {
                $entryitem = $PIVOTX['db']->read_entry($item_id);

                if (in_array($shop_extrascategory,$entryitem['category'])) {
                    $items[$item_id] = $item_items;
                }
            }
        }

        return $items;
    }
    
    public function setTemplate($key, $template) {
        $this->templates[$key] = $template;
    }

    public function getTemplate($key) {
        if(!$this->templates && !empty($this->templates[$key])) {
            return $this->templates[$key];
        } else {
            return false;
        }
    }
    
    /**
     * A simple static function to show a price in cents formatted to whole euro's / dollars / etc.
     */
    public static function renderPrice($amount, $currency='default', $pricetemplate=false) {
        global $PIVOTX;

        if($currency=='default') {
            $currency = $PIVOTX['config']->get('shop_currency', 'EUR');
        }

        $currencies = _shop_currency_types();
        $currency = $currencies[$currency];

        $amount = number_format($amount/100, 2, ',', '.');

        if(!is_object($this) && !$pricetemplate) {
            $pricetemplate = '<span class="price" itemprop="offers" itemscope itemtype="http://schema.org/Offer"><span itemprop="price"><span class="currency">%currency%</span> <span class="amount">%amount%</span></span></span>';
        } elseif($pricetemplate != $this->getTemplate('price')) {
            $this->templates['price'] = '<span class="price"><span class="currency">%currency%</span> <span class="amount">%amount%</span></span>';
            $pricetemplate = $this->templates['price']; 
        }

        $output = str_replace('%currency%', $currency, $pricetemplate);
        $output = str_replace('%amount%', $amount, $output);

        return $output;
    }
    

    /**
     * Show the price
     */
    public static function itemPriceSingle(&$entry) {
        if($entry['extrafields']['item_price']) {
            $entry['extrafields']['item_price_excl_tax'] = $entry['extrafields']['item_price'];
            $entry['extrafields']['item_price_tax_amount'] = $entry['extrafields']['item_price_excl_tax'] * $entry['extrafields']['item_tax'];
            $entry['extrafields']['item_price_incl_tax'] = $entry['extrafields']['item_price_excl_tax'] + $entry['extrafields']['item_price_tax_amount'];
        } elseif($entry['item_price']) {
            $entry['item_price_excl_tax'] = $entry['item_price'];
            $entry['item_price_tax_amount'] = $entry['item_price_excl_tax'] * $entry['item_tax_percentage'];
        }
        //debug_printr($entry['extrafields']);
        return $entry;
    }
    
    /**
     * Show the price * number of items
     */
    public static function itemPriceTotal(&$entry, $no_items=1) {
        if($entry['extrafields']['item_price']) {
            $entry['extrafields']['item_total_excl_tax'] = $entry['extrafields']['item_price'] * $no_items;
            $entry['extrafields']['item_total_tax_amount'] = $entry['extrafields']['item_price_tax_amount'] *  $no_items;
            $entry['extrafields']['item_total_incl_tax'] = $entry['extrafields']['item_total_excl_tax'] + $entry['extrafields']['item_total_tax_amount'];
        } elseif($entry['item_price']) {
            $entry['item_total_excl_tax'] = $entry['item_price'] * $no_items;
            $entry['item_total_tax_amount'] = $entry['item_price_tax_amount'] *  $no_items;
            $entry['item_total_incl_tax'] = $entry['item_total_excl_tax'] + $entry['item_total_tax_amount'];
        }
        //debug_printr($entry['extrafields']);
        
        return $entry;
    }


    /**
     * Calculate the total amounts and taxes
     */
    public function calculateTotals() {
        global $PIVOTX;

        // get all products
        foreach($this->items as $item_id => $items) {
            //debug('item id: '. $item_id .' == items: '. print_r($items, true));
            // load items from cart
            //debug('load items from cart');
            foreach($items as $variant => $item) {
                // check if the cart is a cart, or has already loaded the items from the order
                if(!$item['order_datetime']) {
                    // because item amount is overriden by read_entry
                    $amount = $item['amount'];

                    //debug('item id: '. $item_id .' == variant: '. $variant .' == item: '. print_r($item, true));
                    $item = $PIVOTX['db']->read_entry($item_id);

                    // load single prices
                    $item = $this->itemPriceSingle($item);

                    // load total prices
                    $item = $this->itemPriceTotal($item, $amount);
                    //debug_printr($item['extrafields']);
    
                    $this->totals['cumulative_incl_tax'] += $item['extrafields']['item_total_incl_tax'];
                    $this->totals['cumulative_excl_tax'] += $item['extrafields']['item_total_excl_tax'];
                    $this->totals['cumulative_tax'][($item['extrafields']['item_tax']*100)] += $item['extrafields']['item_total_tax_amount'];
                    $this->totals['number_of_items'] += $amount;
                } else {
                    //debug('adding totals');
                    // load single prices
                    $item = $this->itemPriceSingle($item);

                    // load total prices
                    $item = $this->itemPriceTotal($item, $item['order_no_items']);
                    //debug_printr($item['extrafields']);
                    //debug_printr($item);
                    
                    // items were preloaded by $this->loadOrder() or another process
                    $this->totals['cumulative_incl_tax'] += $item['item_total_incl_tax'];
                    $this->totals['cumulative_excl_tax'] += $item['item_total_excl_tax'];
                    $this->totals['cumulative_tax'][($item['item_tax_percentage']*100)] += $item['item_total_tax_amount'];
                    $this->totals['number_of_items'] += $item['order_no_items'];
                }
            }
        }  

        if($PIVOTX['config']->get('shop_use_shipping')) {
            $this->totals['cumulative_incl_tax'] += $this->shipping['total']['amount_incl_tax'];
            $this->totals['cumulative_tax'][($this->shipping['tax_rate']*100)] += $this->shipping['fixed']['tax_amount'];
            $this->totals['cumulative_excl_tax'] += $this->shipping['total']['amount_excl_tax'];
        }
        
        if($PIVOTX['config']->get('shop_use_discounts')) {
            $this->totals['cumulative_incl_tax'] += $this->discounts['total']['amount_incl_tax'];
            $this->totals['cumulative_excl_tax'] += $this->discounts['total']['amount_excl_tax'];
        }
        
        //debug_printr($this->totals);
    }
    
    /**
     * Calculate the shipping amounts
     */
    public function calculateShipping() {
        global $PIVOTX;

        $this->shipping['type'] = explode(',', $PIVOTX['config']->get('shop_use_shipping'));

        $this->shipping['tax_rate'] = 1*$PIVOTX['config']->get('shop_shipping_tax_rate', '0.19');
        //debug_printr($shippingtype);
        if(in_array('fixed', $this->shipping['type'])) {
            $this->shipping['fixed']['amount_incl_tax'] = $PIVOTX['config']->get('shop_shipping_fixed_amount', '500');
            $this->shipping['fixed']['amount_excl_tax'] = $this->shipping['fixed']['amount_incl_tax'] / (1+$this->shipping['tax_rate']);
            $this->shipping['fixed']['tax_amount'] = $this->shipping['fixed']['amount_incl_tax'] - $this->shipping['fixed']['amount_excl_tax'];
        } else {
            $this->shipping['fixed']['amount_incl_tax'] = 0;
            $this->shipping['fixed']['tax_amount'] = 0;
            $this->shipping['fixed']['amount_excl_tax'] = 0;
        }

        // get totals from all shipping
        // currently only fixed
        $this->shipping['total']['amount_incl_tax'] += $this->shipping['fixed']['amount_incl_tax'];
        $this->shipping['total']['tax_amount'] += $this->shipping['fixed']['tax_amount'];
        $this->shipping['total']['amount_excl_tax'] += $this->shipping['fixed']['amount_excl_tax'];

        // prettify output for totals
        $this->shipping['total']['display'] = $this->renderPrice($this->shipping['total']['amount_incl_tax']);
        
        //debug_printr($this->shipping);

    }
    
    /**
     * Calculate the discount amounts
     */
    public function calculateDiscounts() {
        $this->discounts['total']['amount_incl_tax'] = 0;
        $this->discounts['total']['tax_amount'] = 0;
        $this->discounts['total']['amount_excl_tax'] = 0;
    }
    
    /**
     * Set display version
     *
     * compact|minimal|normal|full|email|checkout
     */
    public function setDisplay($var) {
        $this->display = $var;
    }

    /**
     * Show the full cart
     */
    public function renderCart() {
        global $PIVOTX;
	
        $cartoutput = '';

        $render_extras = false;

        switch ($this->display) {
            case 'normal':
                $cartoutput .= '<h4>'.st('Shopping cart'). "</h4>";
                // fall-through

            case 'full':
                $render_extras = true;
                $oddeven = false;
                $counter = 0;

                $cartoutput .= '<table class="cart '.$this->display.'">';
                $cartoutput .= '<thead><tr class="header">';
                $cartoutput .= '<th scope="col">'. st('Product').'</th>';
                $cartoutput .= '<th scope="col">'. st('Price').'</th>';
                $cartoutput .= '<td colspan="3">&nbsp;</td>';
                $cartoutput .= '<th scope="col">'. st('Total').'</th>';
                $cartoutput .= '</tr></thead>';
                $cartoutput .= '<tbody>';
                $items = $this->filterItemsProductsOnly();
                foreach($items as $item_id => $items) {
                    foreach($items as $variant => $item) {
                        $oddeven = ($oddeven!='odd')?'odd':'even';
                        if($countclass == 0) {
                            $countclass = 'first';
                        } else {
                            $countclass = '';
                        }
                        $itemcartid = 'item_'.$item_id.'_'.$variant;
                        $itemrow =  $this->renderItem($item_id, $item['amount'], $variant);
                        
                        //debug_printr($itemrow);
                        $cartoutput .= '<tr id="'.$itemcartid.'" class="'.$oddeven.' '.$countclass.'">';
                        //$cartoutput .= '<td>'. join('</td><td>', $itemrow).'</td>';
                        $cartoutput .= '<td class="producttitle">'. $itemrow['title'] .' <br />';
                        $cartoutput .= $itemrow['variant'] .'</td>';
                        $cartoutput .= '<td class="singleprice">'. $itemrow['price_single'] .'</td>';
                        $cartoutput .= '<td class="timesx">'. $itemrow['timesx'] .'</td>';
                        $cartoutput .= '<td class="updateform">'. $itemrow['update_buttons'] .'</td>';
                        $cartoutput .= '<td class="deleteform">'. $itemrow['delete_buttons'] .'</td>';
                        $cartoutput .= '<td class="totalprice">'. $itemrow['price_total'] .'</td>';
                        $cartoutput .= '</tr>';
                        $counter++;
                    }
                }
                $cartoutput .= '</tbody>';
                $cartoutput .= '</table>';

                break;
            case 'checkout':
            case 'email':
                $oddeven = false;
                $counter = 0;

                $cartoutput .= '<table class="cart '.$this->display.'">';
                $cartoutput .= '<thead><tr class="header">';
                $cartoutput .= '<th scope="col">'. st('Product').'</th>';
                $cartoutput .= '<th scope="col">'. st('Price').'</th>';
                $cartoutput .= '<td colspan="2">&nbsp;</td>';
                $cartoutput .= '<th scope="col">'. st('Total').'</th>';
                $cartoutput .= '</tr></thead>';
                $cartoutput .= '<tbody>';
                foreach($this->items as $item_id => $items) {
                    foreach($items as $variant => $item) {
                        $oddeven = ($oddeven!='odd')?'odd':'even';
                        if($countclass == 0) {
                            $countclass = 'first';
                        } else {
                            $countclass = '';
                        }
                        $itemcartid = 'item_'.$item_id.'_'.$variant;
                        $itemrow =  $this->renderItem($item_id, $item['amount'], $variant);
                        
                        //debug_printr($itemrow);
                        $cartoutput .= '<tr id="'.$itemcartid.'" class="'.$oddeven.' '.$countclass.'">';
                        //$cartoutput .= '<td>'. join('</td><td>', $itemrow).'</td>';
                        $cartoutput .= '<td class="producttitle">'. $itemrow['title'] .' <br />';
                        $cartoutput .= $itemrow['variant'] .'</td>';
                        $cartoutput .= '<td class="singleprice">'. $itemrow['price_single'] .'</td>';
                        $cartoutput .= '<td class="timesx">'. $itemrow['timesx'] .'</td>';
                        $cartoutput .= '<td class="itemamount">'. $item['amount'] .'</td>';
                        $cartoutput .= '<td class="totalprice">'. $itemrow['price_total'] .'</td>';
                        $cartoutput .= '</tr>';
                        $counter++;
                    }
                }
                $cartoutput .= '</tbody>';
                $cartoutput .= '</table>';

                break;
            case 'compact':
                
                $cartoutput .= '<h4>'.st('Shopping cart'). "</h4>";
                $cartoutput .= '<ul class="cart '.$this->display.'">';
                foreach($this->items as $item_id => $items) {
                    foreach($items as $variant => $item) {
                        $itemcartid = 'item_'.$item_id.'_'.$variant;
                        $cartoutput .= '<li id="'.$itemcartid.'">'. join(' ', $this->renderItem($item_id, $item['amount'], $variant)) .'</li>';
                    }
                }
                $cartoutput .= '</ul>';
                break;
            case 'minimal':
            default:
                break;
        }

        $cartoutput .= $this->renderDiscounts();
        $cartoutput .= $this->renderShipping();
        if ($render_extras) {
            $cartoutput .= $this->renderExtras();
        }
        $cartoutput .= $this->renderTotals();

        switch ($this->display) {
            case 'normal':
            case 'full':
                $shop_homepagelink = $PIVOTX['config']->get('shop_default_homepage');
                //$cartoutput .= '<a href="'.$shop_homepagelink.'">'.st('Continue shopping').'</a> ';
                // TODO: checkoutbutton template
                $cartoutput .= '
<form action="" method="get" class="continue '.$this->display.'"><fieldset><div class="formrow formrow_submit">
    <input type="hidden" name="action" value="checkout" />
    <label><a href="'.$shop_homepagelink.'" class="continue_shopping continuebutton button">'.st('Continue shopping').'</a></label>
    <button type="submit" class="button">
        <span>'.st('Order now').'</span>
    </button>
</div></fieldset></form>
';
                break;
            case 'minimal':
            case 'compact':
                $cartoutput .= '<p class="continue '.$this->display.'">';
                $cartoutput .= '<a href="'.$shop_homepagelink.'" class="continue_shopping continuebutton button">'.st('Continue shopping').'</a>';
                $cartoutput .= '<a href="/?action=cart" class="checkout checkoutbutton button">'.st('View cart').'</a>';
                $cartoutput .= '</p>';
                break;
            case 'checkout':
            case 'email':
            default:
                break;
        }
        
        return '<div class="shoppingcart '.$this->display.'">'.$cartoutput.'</div>';
    }
    
    /**
     * Get and show the total amounts and taxes
     */
    public function renderTotals() {
        // template replacements
        $this->totals['output']['label_number_of_items'] = st('Total %d items');
        $this->totals['output']['number_of_items'] = $this->totals['number_of_items'];
        $this->totals['output']['label_total_excl_tax'] = st('Total excl. tax');
        $this->totals['output']['total_excl_tax'] = $this->renderPrice($this->totals['cumulative_excl_tax']);
        foreach ($this->totals['cumulative_tax'] as $key => $tax_amount) {
            $this->totals['output']['label_tax_percentage_'.$key] = st('Tax %d%%');
            $this->totals['output']['tax_percentage_'.$key] = $this->renderPrice($tax_amount);
        }
        $this->totals['output']['label_total_incl_tax'] = st('Total incl. tax');
        $this->totals['output']['total_incl_tax'] = $this->renderPrice($this->totals['cumulative_incl_tax']);
        
        // direct string output
        //$totalsoutput .= '<pre>'. print_r($this->totals,true).'</pre>';
        switch ($this->display) {
            case 'full':
            case 'normal':
            case 'checkout':
                $totalsoutput = '<table class="totals '.$this->display.'">';
                $totalsoutput .= '<tr class="odd first totalexcl"><td colspan="2">'.st('Total') .' '. $this->totals['number_of_items'] .' '. st('items') .'</td></tr>';
                if($this->showvatincart==true) {
                    $totalsoutput .= '<tr class="even totalexcl"><th>'.st('Total excl. tax') .'</th><td>'. $this->renderPrice($this->totals['cumulative_excl_tax']) .'</td></tr>';
                    foreach ($this->totals['cumulative_tax'] as $key => $tax_amount) {
                        $totalsoutput .= '<tr class="odd tax"><th>'.st('Tax') .' '. round($key) .'%</th><td>'. $this->renderPrice($tax_amount).'</td></tr>';
                    }
                }
                $totalsoutput .= '<tr class="even totalincl"><th>'.st('Total incl. tax') .'</th><td>'. $this->renderPrice($this->totals['cumulative_incl_tax']) .'</td></tr>';
                $totalsoutput .= '</table>';
                break;
            case 'minimal':
            case 'compact':
                $totalsoutput = '<div class="totals '.$this->display.'">';
                $totalsoutput .= '<div class="totalamount">'. st('Total') .' '. $this->totals['number_of_items'] .' '. st('items') ."</div>\n";
                $totalsoutput .= '<div class="totalincl">'. st('Total incl. tax') .' '. $this->renderPrice($this->totals['cumulative_incl_tax']) ."</div>\n";
                $totalsoutput .= '</div>';
                break;
            case 'email':
            default:
                $totalsoutput = '<div class="totals '.$this->display.'">';
                $totalsoutput .= '<div class="totalexcl">'. st('Total excl. tax') .' '. $this->renderPrice($this->totals['cumulative_excl_tax']) ."</div>\n";
                foreach ($this->totals['cumulative_tax'] as $key => $tax_amount) {
                    $totalsoutput .= '<div class="taxes">'. st('Tax') .' '. round($key) .'% '. $this->renderPrice($tax_amount) ."</div>\n";
                }
                $totalsoutput .= '<div class="totalincl">'. st('Total incl. tax') .' '. $this->renderPrice($this->totals['cumulative_incl_tax']) ."</div>\n";
                $totalsoutput .= '</div>';
                break;
        }

        return $totalsoutput;
    }

    /**
     * Get and show the shipping amounts
     */
    public function renderShipping() {
        // template replacements
        $this->totals['output']['label_shipping_incl_tax'] = st('Shipping incl. tax');
        $this->totals['output']['shipping_incl_tax'] = $this->renderPrice($this->shipping['total']['amount_incl_tax']);
        $this->totals['output']['label_shipping_excl_tax'] = st('Shipping excl. tax');
        $this->totals['output']['shipping_excl_tax'] = $this->renderPrice($this->shipping['total']['amount_excl_tax']);
        
        // direct string output
        //$totalsoutput .= '<pre>'. print_r($this->totals,true).'</pre>';
        switch ($this->display) {
            case 'normal':
            case 'full':
                $shippingoutput = '<p class="shipping '.$this->display.'">';
                $shippingoutput .= st('Shipping incl. tax') .' '. $this->renderPrice($this->shipping['total']['amount_incl_tax']);
                //$shippingoutput .= st('Shipping excl. tax') .' '. $this->renderPrice($this->shipping['total']['amount_excl_tax']) .'<br />';
                $shippingoutput .= '</p>';
                break;
            case 'compact':
                $shippingoutput = '<p class="shipping '.$this->display.'">';
                $shippingoutput .= st('Shipping') .' '. $this->renderPrice($this->shipping['total']['amount_incl_tax']);
                $shippingoutput .= '</p>';
                break;
            case 'checkout':
            case 'email':
            default:
                $shippingoutput = '<p class="shipping '.$this->display.'">';
                $shippingoutput .= st('Shipping incl. tax') .' '. $this->renderPrice($this->shipping['total']['amount_incl_tax']);
                //$shippingoutput .= st('Shipping excl. tax') .' '. $this->renderPrice($this->shipping['total']['amount_excl_tax']) .'<br />';
                $shippingoutput .= '</p>';
                break;
            case 'minimal':
                break;
        }
        return $shippingoutput;
    }

    /**
     */
    public function renderExtras() {
        global $PIVOTX;

        $output = '';
        $extras = false;

        $shop_extrascategory = $PIVOTX['config']->get('shop_extrascategory', 'shop');
        if ($shop_extrascategory != '') {
            $extras = $PIVOTX['db']->read_entries(array(
                'cats' => array ( $shop_extrascategory )
            ));

            if (count($extras) == 0) {
                $extras = false;
            }
        }

        if (is_array($extras)) {
            $items = $this->filterItemsExtrasOnly();

            $output .= '<ul class="cart-extras">'."\n";

            $optionid = 200;
            foreach($extras as $extra_id => $extra) {
                $optionchecked = '';

                $entryitem = $PIVOTX['db']->read_entry($extra_id);

                $options = _shop_load_entry_options($entryitem);
                $optionshtml = '';
                $first_option = false;
                foreach($options as $key => $label) {
                    $checkhref = '/?action=editextra&amp;entry='.$entryitem['uid'];
                    $checkhref .= '&amp;option='.rawurlencode($key);

                    $checked = '';
                    if (isset($items[$entryitem['uid']])) {
                        if ($items[$entryitem['uid']][$key]['amount'] == 1) {
                            $checked = ' checked="checked"';
                        }
                    }

                    $optionshtml .= "\t\t".'<label class="check"><input type="radio" data-href="'.$checkhref.'" name="item_option_'.$uid.'" value="'.htmlspecialchars($key).'"'.$checked.' /> '.htmlspecialchars($label).'</label>'."\n";
                    if ($first_option === false) {
                        $first_option = $key;
                    }
                }

                $checkhref = '/?action=editextra&amp;entry='.$entryitem['uid'];
                if (isset($items[$entryitem['uid']])) {
                    $optionchecked = ' checked="checked"';
                    $checkhref     = '/?action=delextra&amp;entry='.$entryitem['uid'];
                }
                else if ($first_option !== false) {
                    $checkhref .= '&amp;option='.rawurlencode($first_option);
                }

                $uid          = $entryitem['uid'];
                $title        = htmlspecialchars($entryitem['extrafields']['selection_text']) . ' ' . _shop_pricedisplay($entryitem) . '';
                $introduction = parse_intro_or_body($entryitem['introduction']);

                $output .= <<<THEEND
    <li>
        <div class="check">
            <label><input type="checkbox" data-href="$checkhref" name="item_checkbox_$uid"$optionchecked> $title</label>
        </div>
THEEND;

                if (isset($items[$entryitem['uid']])) {
                    $output .= <<<THEEND
        <div class="content">
            <div class="introduction">$introduction</div>
            
            <div class="value">
$optionshtml
            </div>
        </div>
    </li>
THEEND;
                }
            }

            $output .= '</ul>'."\n";
        }

        return $output;
    }

    /**
     * Get and show the discount amounts
     */
    public function renderDiscounts() {
        // template replacements
        $this->totals['output']['label_discounts_incl_tax'] = st('Discounts incl. tax');
        $this->totals['output']['discounts_incl_tax'] = 0;
        $this->totals['output']['label_discounts_excl_tax'] = st('Discounts excl. tax');
        $this->totals['output']['discounts_excl_tax'] = 0;
        
        // direct string output
        return '';
        
    }


    /**
     * Show a single item
     *
     * TODO: make an option for prices incl or excl taxes (only does price including taxes now)
     */
    public function renderItem($item_id, $amount, $item_variant='default') {
        global $PIVOTX;
        $entries[$item_id] = $PIVOTX['db']->read_entry($item_id);
        //debug_printr($entries[$key]);
        if($item_variant!='default' && $item_variant!='0') {
            $entries[$item_id]['item_product_options'] = _shop_load_entry_options($entries[$item_id]);
            $entries[$item_id]['option_key'] = $item_variant;
            $entries[$item_id]['option_label'] = $entries[$item_id]['item_product_options'][$item_variant];

            $option_label = '<span class="option">'.$entries[$item_id]['option_label'].'</span>';
        } else {
            $option_label = '';
        }
        
        if($entries[$item_id]['item_cart_title']=='') {
            if($entries[$item_id]['extrafields']['cart_title']!="") {
                $entries[$item_id]['item_cart_title'] = $entries[$item_id]['extrafields']['cart_title'];
            } else {
                $entries[$item_id]['item_cart_title'] = $entries[$item_id]['title'];
            }
        }
        
        // load single prices
        $entries[$item_id] = $this->itemPriceSingle($entries[$item_id]);
        
        // load total prices
        $entries[$item_id] = $this->itemPriceTotal($entries[$item_id], $amount);

        // compact|minimal|normal|full|email|checkout
        switch ($this->display) {
            case 'normal':
            case 'full':
                $output['title'] = '<a href="'. $entries[$item_id]['link'] .'" class="itemlink">'. $entries[$item_id]['item_cart_title'] .'</a>';
                $output['variant'] = $option_label;
                
				$params['no_items'] = $amount;
				$params['text'] = st('Update');
				$params['class'] = 'update';
				$params['action'] = 'update';
				$params['showqty'] = 1;
				$params['showlabels'] = 0;

				$params['option_key'] = $entries[$item_id]['option_key'];
				$params['option_label'] = $entries[$item_id]['option_label'];
                
                $output['price_single'] = st('&agrave;').' '. $this->renderPrice($entries[$item_id]['extrafields']['item_price_incl_tax']);
                $output['timesx'] = '<span>&times;</span>';
                $output['update_buttons'] = _shop_buybutton($entries[$item_id], $params);
                $output['price_total'] = $this->renderPrice($entries[$item_id]['extrafields']['item_total_incl_tax']);
                $output['delete_buttons'] = '<a href="'. _shop_removelink($entries[$item_id]) .'" class="removelink"><span>'. st("Remove") .'</span></a>';
                break;
            case 'checkout':
                $output['amount'] = $amount;
                $output['timesx'] = '<span>&times;</span>';
                //$output['title'] = '<a href="'. $entries[$item_id]['link'] .'" class="itemlink">'. $entries[$item_id]['title'] .'</a>';
                $output['title'] =  $entries[$item_id]['item_cart_title'];
                $output['variant'] = $option_label;
                $output['price_single'] = st('&agrave;').' '. $this->renderPrice($entries[$item_id]['extrafields']['item_price_incl_tax']);
                $output['timesx'] = '<span>&times;</span>';
                $output['price_total'] = $this->renderPrice($entries[$item_id]['extrafields']['item_total_incl_tax']);
                break;
            case 'email':
                $output['amount'] = $amount;
                $output['timesx'] = ' &times; ';
                $output['title'] = $entries[$item_id]['item_cart_title'];
                $output['variant'] = strip_tags($option_label);
                $output['price_single'] = st('&agrave;').' '. $this->renderPrice($entries[$item_id]['extrafields']['item_price_incl_tax']);
                $output['timesx'] = '<span>&times;</span>';
                $output['price_total'] = $this->renderPrice($entries[$item_id]['extrafields']['item_total_incl_tax']);

                break;
            case 'minimal':
            case 'compact':
            default:
                $output['amount'] = $amount;
                $output['timesx'] = '<span>&times;</span>';
                $output['title'] = '<a href="'. $entries[$item_id]['link'] .'" class="itemlink">'. $entries[$item_id]['item_cart_title'] .'</a>';
                $output['variant'] = $option_label;
                break;
        }
        return $output;
        
    }
    
    /**
     * Load all items in the cart and return them in an array
     */
    public function loadCartItems() {
        global $PIVOTX;
        foreach($this->items as $item_id => $item) {
        	if(is_array($item)) {
                foreach($item as $key_option => $amount) {
                    $entries[$item_id] = $PIVOTX['db']->read_entry($item_id);
                    $cart_item = array();
                    $cart_item['item_id'] = $item_id;
                    $cart_item['item_option'] = $key_option;
                    $cart_item['item_code'] = $entries[$item_id]['extrafields']['item_code'];
                    $cart_item['item_title'] = $entries[$item_id]['title'];
                    $cart_item['item_cart_title'] = $entries[$item_id]['extrafields']['cart_title'];
                    $cart_item['item_content'] = $entries[$item_id]['extrafields']['item_code'];
                    $cart_item['item_price'] = $entries[$item_id]['extrafields']['item_price'];
                    $cart_item['item_price_incl_tax'] = $entries[$item_id]['extrafields']['item_price'] + ($entries[$item_id]['extrafields']['item_price'] * $entries[$item_id]['extrafields']['item_tax']);
                    $cart_item['item_tax_percentage'] = $entries[$item_id]['extrafields']['item_tax'];
                    $cart_item['order_no_items'] = $amount['amount'];
                    $cart_items[] = $cart_item;
                }
            }
        }
        return $cart_items;
    }
    
    
    protected function getSessionVar($var) {
        global $PIVOTX;
        $value = $PIVOTX['session']->getValue($var);
        
        //debug('get session value : '.$var);
        //debug_printr(array('SESSION'=>$_SESSION, 'PIVOTX-session'=>$PIVOTX['session'], 'value'=>$value));
        
        return $value;
    }
    
    protected function setSessionVar($var, $value) {
        global $PIVOTX;
        $PIVOTX['session']->setValue($var, $value);
        //debug('set session value : '.$var);
        //debug_printr(array('SESSION'=>$_SESSION, 'PIVOTX-session'=>$PIVOTX['session'], 'value'=>$value));
    }
    
}