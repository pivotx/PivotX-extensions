<?php
/**
 * Class to work with Shop Entries, using the sql storage model.
 *
 * TODO: oopsify this
 */
class ShopSql {

    /**
     * Initialisation.
     *
     * @return ShopSql
     */
    function ShopSql() {
        global $PIVOTX;

        // Set the names for the tables we use.
        $prefix = safe_string($PIVOTX['config']->get('db_prefix'), true);
        $this->shoptable = $prefix.'order';
        $this->shopitemtable = $prefix.'order_item';
        $this->shoplogtable = $prefix.'order_log';

        // Set up DB connection
        $this->sql = new sql(
            'mysql',
            $PIVOTX['config']->get('db_databasename'),
            $PIVOTX['config']->get('db_hostname'),
            $PIVOTX['config']->get('db_username'),
            $PIVOTX['config']->get('db_password')
        );
    }
	
	/**
	 * Get a single Order by its id
	 *
	 * @param integer $order_id
	 * @return array
	 */
    function getOrder($order_id) {
		$qry = array();
		$qry['select'] = "*";
		$qry['from'] = $this->shoptable;
		$qry['where'][] = "order_id=" . $order_id;
		$tmpquery = $this->sql->build_select($qry);

		$this->sql->query();
		$order = $this->sql->fetch_row();

        if($order) {
            $order['order_items'] = $this->getOrderItems($order['order_id']);
        }
        
		return $order;
    }
    	
    /**
	 * Get a single Order by its public id and hash
	 *
	 * @param integer $order_id
	 * @return array
	 */
    function getOrderByCode($order_code, $order_hash) {
		$qry = array();
		$qry['select'] = "*";
		$qry['from'] = $this->shoptable;
		$qry['where'][] = "order_public_code='" . $order_code ."'";
		$qry['where'][] = "order_public_hash='" . $order_hash ."'";
		$qry['where'][] = "order_status != 'junk'";
		$tmpquery = $this->sql->build_select($qry);

		$this->sql->query();
		$order = $this->sql->fetch_row();

        if($order) {
            $order['order_items'] = $this->getOrderItems($order['order_id']);
        }

		return $order;
    }
    	
    /**
	 * Get a single Order by its external payment key
	 *
	 * @param integer $order_id
	 * @return array
	 */
    function getOrderByPayment($paymentcode) {
		$qry = array();
		$qry['select'] = "*";
		$qry['from'] = $this->shoptable;
		$qry['where'][] = "payment_external_code='" . $paymentcode ."'";
		$tmpquery = $this->sql->build_select($qry);

        //debug($tmpquery);

		$this->sql->query();
		$order = $this->sql->fetch_row();

        if($order) {
            $order['order_items'] = $this->getOrderItems($order['order_id']);
        }

		return $order;
    }
    
	/**
	 * Delete a single Order
	 *
	 * @param integer $order_id
	 */
	function delOrder($order_id) {
		$qry = array();
		$qry['delete'] = $this->shoptable;
		$qry['where'] = "order_id=" . $order_item_id;
		$tmpquery = $this->sql->build_delete($qry);

		$this->sql->query();
	}
	
	/**
	 * Save a single Order
	 * Create it when it does not exist yet
	 *
	 * @param array $submission
	 * @return array $submission
	 */
	function saveOrder($submission) {
		$value = array(
			// order info
			'order_id' => $submission['order_id'],
			'order_public_code' => $submission['order_public_code'],
			'order_public_hash' => $submission['order_public_hash'],
			'order_datetime' => ($submission['order_datetime'])?$submission['order_datetime']:date("Y-m-d H:i:s", time()),
			'order_status' => ($submission['order_status'])?$submission['order_status']:'unknown',

			// user info
			'user_name' => $submission['user_name'],
			'user_email' => $submission['user_email'],
			'user_phone' => $submission['user_phone'],
			
			// address info
			'user_address' => !empty($submission['user_address_address'])?$submission['user_address_address']:$submission['user_address'],
			'user_postcode' => !empty($submission['user_address_postcode'])?$submission['user_address_postcode']:$submission['user_postcode'],
			'user_city' => !empty($submission['user_address_city'])?$submission['user_address_city']:$submission['user_city'],
			'user_country' => !empty($submission['user_address_country'])?$submission['user_address_country']:$submission['user_country'],
			
			// internal info
			'user_ip' => $submission['user_ip'],
			'user_hostname' => $submission['user_hostname'],
			'user_browser' => $submission['user_browser'],

            // shipping details
			'shipping_handler' => $submission['shipping_handler'],
			'shipping_external_code' => $submission['shipping_external_code'],
			'shipping_status' => $submission['shipping_status'],
			'shipping_datetime' => ($submission['shipping_datetime'])?$submission['shipping_datetime']:'0000-00-00 00:00:00',

            // payment details
			'payment_provider' => $submission['payment_provider'],
			'payment_external_code' => $submission['payment_external_code'],
			'payment_status' => $submission['payment_status'],
			'payment_datetime' => ($submission['payment_datetime'])?$submission['payment_datetime']:'0000-00-00 00:00:00',
            'payment_amount_total' => $submission['payment_amount_total'],
			'payment_message' => $submission['payment_message'],
		);

		// yeah, we're checking for empty null ans more here, but BPB should do that easier
		if (empty($submission['order_id']) || $submission['order_id']=="" || $submission['order_id']==0 || $submission['order_id']==null || $submission['order_id']=='new') {
			unset($value['order_id']);
			$qry=array();
			$qry['into'] = $this->shoptable;
			$qry['value'] = $value;
			$tmpquery = $this->sql->build_insert($qry);
			$this->sql->query();
			$submission['order_id'] = $this->sql->get_last_id();
		} else {
			$qry=array();
			$qry['update'] = $this->shoptable;
			$qry['value'] = $value;
			$qry['where'] = "order_id='" . $submission['order_id'] . "'";
			$tmpquery = $this->sql->build_update($qry);
			$this->sql->query();
		}

		//debug('save order: '. $tmpquery);
        
        $order_id = $submission['order_id'];
        // update orderitems
        if(is_array($submission['order_items']) && !empty($submission['order_items'])) {
            foreach($submission['order_items'] as $key => $item) {
                if(isset($item['order_item_action']) && $item['order_item_action']=='delete') {
                    $this->delOrderItem($item['order_item_id']);
                    unset($submission['order_items'][$key]);
                } else {
                    $newkey = $this->saveOrderItem($order_id, $item);
                    unset($submission['order_items'][$key]);
                }
            }
        }
        // re-fetch all orderitems
        $submission['order_items'] = $this->getOrderItems($order_id);
        
		// Return the uid of the page we just inserted / updated..
		return $submission;
	}


	/**
	 * Update a single order key/value pair
	 *
	 * TODO: Make this actually usefull
	 *
	 * @param integer $order_id
	 * @param array $values
	 * @return mixed
	 */
    function updateOrder($order_id, $values) {
		$qry=array();
		$qry['update'] = $this->shoptable;
		$qry['value'] = $values;
		$qry['where'] = "order_id=". $order_id;
		$tmpquery = $this->sql->build_update($qry);
		
		//debug('updateOrder with: '.$tmpquery);
		$this->sql->query();
    }
	
	/**
	 * Get a single Orderitem by its id
	 *
	 * @param integer $order_item_id
	 * @return array
	 */
    function getOrderItem($order_item_id) {
		$qry = array();
		$qry['select'] = "*";
		$qry['from'] = $this->shopitemtable;
		$qry['where'][] = "order_item_id=" . $order_item_id;
		$tmpquery = $this->sql->build_select($qry);

		$this->sql->query();
		$orderitem = $this->sql->fetch_row();
		return $orderitem;        
    }
    
	/**
	 * Get all Orderitems by their order_id
	 *
	 * @param integer $order_id
	 * @return array
	 */
    function getOrderItems($order_id) {
		$qry = array();
		$qry['select'] = "*";
		$qry['from'] = $this->shopitemtable;
		$qry['where'][] = "order_id=" . $order_id;
		$tmpquery = $this->sql->build_select($qry);

		$this->sql->query();
		$orderitems = $this->sql->fetch_all_rows();
        if(is_array($orderitems)) {
            foreach($orderitems as $item) {
                $outputitems[$item['order_item_id']] = $item;
            }
            return $outputitems;
        } else {
            return false;
        }
    }
    
	/**
	 * Delete a single OrderItem
	 *
	 * @param integer $order_item_id
	 */
	function delOrderItem($order_item_id) {
		$qry = array();
		$qry['delete'] = $this->shopitemtable;
		$qry['where'] = "order_item_id=" . $order_item_id;
		$tmpquery = $this->sql->build_delete($qry);

		//debug("delFormbuilderLog\n"  . $tmpquery);

		$this->sql->query();
	}

	/**
	 * Save a single OrderItem
	 * Create it when it does not exist yet
	 *
	 * @param array $item
	 * @return int
	 */
	function saveOrderItem($order_id, $item) {
		//debug('updating item to order: '.$order_id ."\nadding item:");
		//debug_printr($item);
		$value = array(
			'order_item_id' => $item['order_item_id'],
			'order_id' => $order_id,
			'order_datetime' => ($item['order_datetime'])?$item['order_datetime']:date("Y-m-d H:i:s", time()),
			'order_no_items' => ($item['order_no_items'])?$item['order_no_items']:1,

			'item_id' => $item['item_id'],
			'item_code' => $item['item_code'],
			'item_option' => $item['item_option'],
			'item_title' => $item['item_title'],
			'item_content' => $item['item_content'],
			'item_price' => $item['item_price'],
			'item_price_incl_tax' => $item['item_price_incl_tax'],
			'item_tax_percentage' => $item['item_tax_percentage'],
		);

		if (!isset($value['order_item_id']) || $value['order_item_id']=="" || $value['order_item_id']==0 || $value['order_item_id']==null || $value['order_item_id']=='new') {
			// New item
			//debug('actually, adding item to order: '.$order_id);
			
			unset($value['order_item_id']);
			$qry=array();
			$qry['into'] = $this->shopitemtable;
			$qry['value'] = $value;
			$tmpquery = $this->sql->build_insert($qry);
			$this->sql->query();
			$order_item_id = $this->sql->get_last_id();
		} else {
			$qry=array();
			$qry['update'] = $this->shopitemtable;
			$qry['value'] = $value;
			$qry['where'][] = "order_item_id='" . $value['order_item_id'] . "'";
			$qry['where'][] = "order_id='" . $value['order_id'] . "'";
			$tmpquery = $this->sql->build_update($qry);
			$this->sql->query();
			$order_item_id = $value['order_item_id'];
		}

		// Return the uid of the item we just inserted / updated..
		return $order_item_id;
	}
}