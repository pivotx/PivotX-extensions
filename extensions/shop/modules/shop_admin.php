<?php
/**
 * Adds extrafields and admin screens for product types.
 */

/**
 * Assign a hook for modifying the backend menu
 */
$this->addHook(
    'modify_pivotx_menu',
    'callback',
	'checkoutAdminMenu'
    );

/**
 * Modify the admin menu to add the transactions page
 */
function checkoutAdminMenu($_menu) {
	if(function_exists('addtoTopMenu')) {
		$top = array(
			'sortorder' => 2500,
			'uri' => 'shoptransactions',
			'name' => st('Shop'),
			'description' => st('Overview of shop'),
			'level' => PIVOTX_UL_NORMAL
		);
		$menu_addon =  array(
			array(
				'sortorder' => 1000,
				'uri' => 'shoptransactions',
				'name' => st('Transactions'),
				'description' => st('List and manage transactions'),
				'level' => PIVOTX_UL_ADMIN
			),
			array(
				'sortorder' => 1500,
				'is_divider' => true
			),
			array(
				'sortorder' => 3000,
				'uri' => 'shopadmin',
				'name' => st('Settings'),
				'description' => st('Modify the shop configuration'),
				'level' => PIVOTX_UL_ADMIN
			),
			array(
				'sortorder' => 4000,
				'uri' => 'shopswitch',
				'name' => st('Switch'),
				'description' => st('Turn the shop on or off'),
				'level' => PIVOTX_UL_ADMIN
			)
		);
		addtoTopMenu($_menu[0], $top, $menu_addon);
	}
}


/**
 * The configuration screen for shop
 *
 * @param unknown_type $form_html
 */
function pageShopadmin() {
    global $PIVOTX, $shop_config;
    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_ADMIN);


    $PIVOTX['template']->assign('title', st('Shop settings'));
	
	$shop_configkeys = array(
		'shop_enabled',
		'shop_email_address',
		'shop_email_name',
		'shop_language',
		'shop_category',
		'shop_default_homepage',
		'shop_default_template',
		'shop_default_theme',
		'shop_currency',
		'shop_tax_rates_vat',
		'shop_tax_rates_sales',
		'shop_use_shipping',
		'shop_shipping_fixed_amount',
		'shop_shipping_tax_rate',
		'shop_shipping_handler',
		'shop_use_payment',
		'shop_automatic',
		'shop_builtin_css',
		'shop_email_other_return_tpl'
	);

	// hook for payment plugins
	$PIVOTX['extensions']->executeHook('shop_admin_configkeys', $shop_configkeys);

	// missing checkbox values
	// TODO: put this in the extension
	if(!empty($_POST) && !isset($_POST['shop_mollie_testmode'])) {
		$_POST['shop_mollie_testmode'] = false;
	}
	if(!empty($_POST) && !isset($_POST['shop_automatic'])) {
		$_POST['shop_automatic'] = false;
	}
	if(!empty($_POST) && !isset($_POST['shop_builtin_css'])) {
		$_POST['shop_builtin_css'] = false;
	}
	//debug_printr($_POST);
	
	foreach($shop_configkeys as $shopkey) {
		if(isset($_POST[$shopkey])) {
			//debug($shopkey .': '. print_r($_POST[$shopkey], true));
			if(is_array($_POST[$shopkey])) {
				$shop_config[$shopkey] = $PIVOTX['config']->set($shopkey, join('|',$_POST[$shopkey]));
			} else {
				$shop_config[$shopkey] = $PIVOTX['config']->set($shopkey, $_POST[$shopkey]);
			}
		}
		$shop_config[$shopkey] = $PIVOTX['config']->get($shopkey);
		if(stristr($shop_config[$shopkey], '|')) {
			$shop_config[$shopkey] = explode('|', $shop_config[$shopkey]);
		}
	}
	
	if(empty($shop_config['shop_email_address'])) {
		$email = getDefault($_SERVER['SERVER_ADMIN'], 'info@'.$_SERVER['HTTP_HOST']);
		$shop_config['shop_email_address'] = $PIVOTX['config']->get('shop_email_address', $email);
	}
	
	//debug($_POST['shop_mollie_testmode'] . '-' . $shop_config['shop_mollie_testmode']);

    $form = new Form($key, "", st("Save settings"));

    $types = _shop_currency_types();
	
	if($PIVOTX['config']->get('shop_enabled', false)==false) {

		$form->add( array(
			'type' => 'custom',
			'text' => sprintf("<tr><td colspan='2'>%s</td></tr>", shopConfigWarnings(false))
		));
		
		// turn the shop off
	}
	
	// FIXME: mollie is dependent on euro's
    $form->add( array(
        'type' => 'select',
        'options' => $types,
        'name' => 'shop_currency',
        'isrequired' => 1,
        'label' => st('Default currency'),
        'text' => makeJtip(st('Default currency'), st('At the moment you can only use one currency per site.')),
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'shop_email_address',
        'isrequired' => 1,
        'label' => st('Default email address'),
        'error' => st('You really must enter an email address'),
        'validation' => 'email',
        'text' => makeJtip(st('Default email address'), st('The email address that will be used as sender on all mails from the shop.')),
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'shop_email_name',
        'isrequired' => 1,
        'label' => st('Default shop name'),
        'error' => st('That\'s not a proper name!'),
        'size' => 50,
        'validation' => 'string|minlen=2|maxlen=40',
        'text' => makeJtip(st('Default shop name'), st('The name of the shop, will mostly be used as sender name on all mails from the shop.')),
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'shop_language',
        'isrequired' => 1,
        'label' => st('Default language'),
        'size' => 50,
        'text' => makeJtip(st('Default language'), st('The name of the language file, use &quot;default&quot; for no changes.')),
    ));

	$allcats = $PIVOTX['categories']->getCategories();
	// Make an array where the keys are the same as the values
    foreach($allcats as $cat) {
        $catoptions[$cat['name']] = sprintf("%s (%s)", $cat['display'], $cat['name']);
    }
	
    $form->add( array(
        'type' => 'select',
        'name' => 'shop_category',
        'options' => $catoptions,
        'isrequired' => 1,
        'label' => st('Default shop category'),
        'error' => st('That\'s not a proper category name!'),
        'validation' => 'string|minlen=2|maxlen=40',
        'text' => makeJtip(st('Default shop category'), st('Use the internal category name of the category you want to use for the shop, the category must exist.')),
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'shop_default_homepage',
        'isrequired' => 1,
        'label' => st('Default shop homepage'),
        'error' => st('That\'s not a proper url!'),
        'size' => 50,
        'validation' => 'string|minlen=2|maxlen=40',
        'text' => makeJtip(st('Default shop homepage'), st('Enter the url to your shop homepage here. This can be a category, a weblog or any PivotX url.')),
    ));

    $form->add( array(
        'type' => "hr"
    ));

	$form->add( array(
        'type' => 'custom',
        'text' => sprintf("<tr><td colspan='2'><h3>%s</h3></td></tr>",
            st('Tax settings') )
    ));

    $form->add( array(
        'type' => 'textarea',
        'name' => 'shop_tax_rates_vat',
        'rows' => 3,
        'cols' => 53,
        'label' => st('Value Added Tax'),
        'text' => makeJtip(st('Value Added Tax (VAT)'), st('Values for the shop VAT rates (20% would be 0.2). Comma separated. The default rate is the first. <br>VAT will be calculated for each product independently.')),
    ));
/*
	// FIXME: make it do something
    $form->add( array(
        'type' => 'textarea',
        'name' => 'shop_tax_rates_sales',
        'rows' => 3,
        'cols' => 53,
        'label' => st('Sales TAX'),
        'text' => makeJtip(st('Sales TAX'), st('Values for the shop sales tax. Comma separated. The default rate is the first. <br>Sales tax will be calculated for a complete order during the checkout process.')),
    ));
*/	
    $form->add( array(
        'type' => "hr"
    ));

	$form->add( array(
        'type' => 'custom',
        'text' => sprintf("<tr><td colspan='2'><h3>%s</h3></td></tr>",
            st('Customization settings') )
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'shop_default_theme',
        'isrequired' => 1,
        'label' => st('Default shop theme'),
        'error' => st('That\'s not a proper theme name!'),
        'size' => 50,
        'validation' => 'string|minlen=2|maxlen=40',
        'text' => makeJtip(st('Default shop theme'), st('Use the them name you want to use for the shop, the theme must exist.')),
    ));
	
    $form->add( array(
        'type' => 'text',
        'name' => 'shop_default_template',
        'isrequired' => 1,
        'label' => st('Default shop template'),
        'error' => st('That\'s not a proper category name!'),
        'size' => 50,
        'validation' => 'string|minlen=2|maxlen=40',
        'text' => makeJtip(st('Default shop template'), st('Use the template name you want to use for the shop, the file must exist in your default theme.')),
    ));
	
	$templatename_shop_email_other_return_tpl = dirname(dirname(__FILE__)) .'/'. $shop_config['shop_email_other_return_tpl'];
	if(!file_exists($templatename_shop_email_other_return_tpl)) {

		$form->add( array(
			'type' => 'custom',
			'text' => sprintf("<tr><td colspan='2'><label for='shop_email_other_return_tpl' class='error'>%s</label></td></tr>",
				st('Other mail template') . ' ' . st('was not found at this location') )
		));
		// turn the shop off
		$PIVOTX['config']->set('shop_enabled', false);
        $logmessage = $PIVOTX['config']->get('shop_last_errors');
        $logmessage .= '|Other mail template missing';
        $PIVOTX['config']->set('shop_last_errors', $logmessage);  
	}
	
    $form->add( array(
        'type' => 'text',
        'name' => 'shop_email_other_return_tpl',
        'isrequired' => 1,
        'label' => st('Other mail template'),
        'error' => st('That\'s not a proper template name!'),
        'size' => 50,
        'validation' => 'string|minlen=2|maxlen=60',
        'text' => makeJtip(st('Other mail template'), st('The mail template for other payment messages. The templates are located in the extension direcotry. (usually templates/name_of_template.tpl).')),
    ));

	$form->add( array(
        'type' => 'checkbox',
        'name' => 'shop_automatic',
        'label' => st('Append snippets to default templates'),
        'text' => makeJtip(st('Append snippets to default templates'), st('If this checkbox is disabled you must add the [[addtocart]] and [[shoppingcart]] snippets to your templates. <br>If this checkbox is enabled the <em>add to cart</em> buttons will be automatically added to the  [[introduction]] of the entries and the <em>shopping cart</em> will be appended to the [[widgets]] snippet.')),
    ));

	$form->add( array(
        'type' => 'checkbox',
        'name' => 'shop_builtin_css',
        'label' => st('Use builtin CSS'),
        'text' => makeJtip(st('Use builtin CSS'), st('Use the builtin CSS. Turn this on is you want to use your custom theme CSS.')),
    ));

    $form->add( array(
        'type' => "hr"
    ));

	$form->add( array(
        'type' => 'custom',
        'text' => sprintf("<tr><td colspan='2'><h3>%s</h3></td></tr>",
            st('Shipping settings') )
    ));

    $form->add( array(
        'type' => 'select',
        'options' => array(
			'no' => st('Dont use shipping'),
			'free' => st('Free shipping'),
			'fixed' => st('Fixed shipping amount'),
			'perproduct' => st('Shipping amount per product'),
			'freefrom' => st('Free shipping for orders above a certain amount'),
			'handler' => st('Shipping costs depend on handler')
		),
		'size' => '4',
		'multiple' => true,
        'name' => 'shop_use_shipping',
        'isrequired' => 1,
        'label' => st('Use shipping options'),
        'text' => makeJtip(st('Use shipping options'), st('If you want to use shipping options you should turn this on.')),
    ));

	$form->add( array(
        'type' => 'text',
        'name' => 'shop_shipping_fixed_amount',
        'isrequired' => 1,
        'label' => st('Fixed shipping amount'),
        'error' => st('That\'s not a proper number!'),
        'validation' => 'integer',
        'text' => makeJtip(st('Fixed shipping amount'), st('Enter the fixed shipping amount in cents, including taxes (&euro; 5,- would be 500)')),
    ));
	
    $form->add( array(
        'type' => 'text',
        'name' => 'shop_shipping_tax_rate',
        'isrequired' => 1,
        'label' => st('Tax rate for shipping'),
        'text' => makeJtip(st('Tax rate for shipping'), st('Enter the tax rate for shipping (20% would be 0.2). If no taxes apply use &quot;0&quot;')),
    ));
	
	if(in_array('handler', $shop_config['shop_use_shipping']) ) {
		$shipping_options = array(
				'no' => st('Dont use shipping handlers'),
				'storepickup' => st('Store pickup'),
				'postnl' => st('PostNL mail'),
				'tntexpress' => st('TNT Express mail'),
				'ups' => st('UPS'),
				'fedex' => st('FedEx')
			);
		$PIVOTX['extensions']->executeHook('shop_admin_shipping_handler_options', $shipping_options);
		
		$form->add( array(
			'type' => 'select',
			'options' => $shipping_options,
			'size' => '4',
			'multiple' => true,
			'name' => 'shop_shipping_handler',
			'isrequired' => 1,
			'label' => st('Available shipping handlers'),
			'text' => makeJtip(st('Available shipping handlers'), st('If you want to enable shipping handler choices on checkout you should turn this on.')),
		));
		// hook for shipping plugins
		$PIVOTX['extensions']->executeHook('shop_admin_shipping_handler', $form);
	}
	// hook for shipping plugins
	$PIVOTX['extensions']->executeHook('shop_admin_shipping', $form);
	
	
    $form->add( array(
        'type' => "hr"
    ));

	$form->add( array(
        'type' => 'custom',
        'text' => sprintf("<tr><td colspan='2'><h3>%s</h3></td></tr>",
            st('Payment settings') )
    ));
	
	$payment_options = array(
			'no' => st('No'),
			'other' => st('Offline payment')
		);
	
	// hook for payment plugins
	$PIVOTX['extensions']->executeHook('shop_admin_payment_options', $payment_options);
	
    $form->add( array(
        'type' => 'select',
        'options' => $payment_options,
		'size' => '3',
		'multiple' => true,
        'name' => 'shop_use_payment',
        'isrequired' => 1,
        'label' => st('Use payment options'),
        'text' => makeJtip(st('Use payment options'), st('If you want to use iDEAL via mollie.nl you should turn this on.')),
    ));

	// hook for payment plugins
	$PIVOTX['extensions']->executeHook('shop_admin_payment', $form);
	
    $form->setValues($shop_config);
    $form_html = $form->fetch();

	$PIVOTX['template']->assign('html', $form_html);
    renderTemplate('generic.tpl');
}

function _shop_currency_types() {
	$types = array();
    $types['EUR'] = '&euro;';
    $types['USD'] = '$';
    $types['GBP'] = '&pound;';
    $types['NKR'] = 'NKR';
	return $types;
}


/**
 * Display configuation warnings
 */
$this->addHook(
    'in_pivotx_template',
    'dashboard-before-warnings',
    array('callback' => 'shopConfigWarnings' )
    );

/**
 * Callback function for our hook..
 */
function shopConfigWarnings($print=true) {
    global $PIVOTX;
    // run configurationtest
    $result = shopConfigTest();
	
	if($PIVOTX['config']->get('shop_enabled')==false) {
		$logmessagesarr = explode("|", $PIVOTX['config']->get('shop_last_errors'));
		$logmessages = "<ul>";
		foreach($logmessagesarr as $message) {
			if(trim($message)!="") {
				$logmessages .= '<li>'.$message.'</li>';
			}
		}
		$logmessages .= "</ul>";
        $output = <<< EOM
<div class="warning" style="margin-top: 16px;margin-bottom: 16px">
    <h2 class="sectiontitle"><img src="pics/delete.png" alt="" />Shop disabled</h2>
    <div>
		<h3>The shop is disabled.</h3>
        <p>Check <a href="index.php?page=shopadmin">the configuration</a> and logfile for errors before you <a href="index.php?page=shopswitch">enable</a> it.</p>
		%logmessages%
    </div>
</div>
EOM;
		$output = str_replace('%logmessages%', $logmessages, $output);

	} else {
        $output = <<< EOM
<div class="news" style="margin-top: 16px;margin-bottom: 16px">
    <h2 class="sectiontitle"><img src="pics/accept.png" alt="" />Shop</h2>
    <div>
		<h3>The shop is active.</h3>
        <p>Check <a href="?page=transactions">the transactions</a> or modify the <a href="index.php?page=shopadmin">the configuration</a>.</p>
    </div>
</div>
EOM;
	}
	if($print) {
		print $output;
	} else {
		return $output;
	}
}




function pageShopswitch() {
    global $PIVOTX;

	if($_POST) {
		if($_POST['shoptoggle'] == st('Turn shop on')) {
			$PIVOTX['config']->set('shop_enabled', true);
			$PIVOTX['config']->set('shop_last_errors', false);
		} elseif($_POST['shoptoggle'] == st('Turn shop off')) {
			$PIVOTX['config']->set('shop_enabled', false);
			$logmessage = $PIVOTX['config']->get('shop_last_errors', false);
			$logmessage .= "|manually switched off";
			$PIVOTX['config']->set('shop_last_errors', $logmessage);			
		}
		// run configurationtest
		$result = shopConfigTest();
	}

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_ADMIN);

    $PIVOTX['template']->assign('title', st('Shop Main switch'));

    $PIVOTX['template']->assign('heading', st('Turn the shop on or off. Or see messages about why the shop is turned off.'));
	
	$PIVOTX['template']->assign('html', shopConfigWarnings(false));
	

	
	$shoptoggleform = '<form action="" method="post"><p class="buttons">%button%</p></form>';
	if($PIVOTX['config']->get('shop_enabled', false)==false) {
		$button = '<button name="shoptoggle" type="submit" class="positive" value="'.st('Turn shop on').'"><img src="./pics/tick.png" alt="">'.st('Turn shop on').'</button>';
	} else {
		$button = '<button name="shoptoggle" type="submit" class="negative" value="'.st('Turn shop off').'"><img src="./pics/delete.png" alt="">'.st('Turn shop off').'</button>';				
	}
	$shoptoggleform = str_replace('%button%', $button, $shoptoggleform);
	
	$PIVOTX['template']->assign('form', $shoptoggleform);
    renderTemplate('generic.tpl');

}


/**
 * add a hook for the extrafields eneded in the shop
 */
$this->addHook(
    'in_pivotx_template',
    'entry-body-before',
    array('callback' => 'shopExtrafields' )
    );

/**
 * Callback function for our hook..
 */
function shopExtrafields($entry) {
	global $PIVOTX;

    $output = '
	<br class="shopvisible" />
    <hr class="shop-separator shopvisible" /> 
    <table class="formclass shopvisible" border="0" cellspacing="0" width="650">
        <tbody>

            <tr>
                <td width="140">
                    <label><strong>%title_item_is_available%:</strong></label>
                </td>
				<td width="210">
                    <label class="inlinelabel"><input type="radio" name="extrafields[item_is_available]" class="isproductselect" id="extrafield-item_is_available-no" value="no" %item_is_available-no-selected%/>
                    <span>%label_no%</span></label>
                    <label class="inlinelabel"><input type="radio" name="extrafields[item_is_available]" class="isproductselect" id="extrafield-item_is_available-yes" value="yes" %item_is_available-yes-selected%/>
                    <span>%label_yes%</span></label>
                </td>
                <td width="300">
                    <div class="description">%desc_item_is_available%</div>
                </td>
            </tr>

            <tr class="shopproducts">
                <td>
                    <label><strong>%title_item_price%:</strong></label>
                </td>
                <td>
                    <label><strong>%title_item_price_excl_tax%:</strong></label>
                    <input id="extrafield-item_price" name="extrafields[item_price]" value="%item_price%" type="text" style="width: 100px;" title="%desc_item_price%" />
                </td>
                <td>
                    <label><strong>%title_item_tax%:</strong></label>
                    <select id="extrafield-item_tax" name="extrafields[item_tax]" type="text" style="width: 100px;" title="%desc_item_tax%">
%tax_options%
					</select>
                    <label><strong>%title_item_price_incl_tax%:</strong></label>
                    <input id="extrafield-item_price_incl_tax" name="extrafields[item_price_incl_tax]" value="%item_price_incl_tax%" type="text" style="width: 100px;" title="%desc_item_price_incl_tax%" />
                </td>
            </tr>
            <tr>
                <td>
                </td>
                <td colspan="2">%extra_item_price%</td>
            </tr>

            <tr>
                <td>
                     <label><strong>%title_item_code%:</strong></label>
                </td>
				<td>
					<input id="extrafield-item_code" name="extrafields[item_code]" value="%item_code%" type="text" style="width: 150px;"/>
                </td>
                <td>
                    <div class="description">%desc_item_code%</div>
                </td>
            </tr>

            <tr>
                <td>
                     <label><strong>%title_item_product_options%:</strong></label>
                </td>
				<td colspan="2">
					<textarea id="extrafield-item_product_options" name="extrafields[item_product_options]" class="resizable" style="width:500px; height: 40px;" cols="50" rows="4">%item_product_options%</textarea>
                    <div class="description">%desc_item_product_options%</div>
                </td>
            </tr>

        </tbody>
    </table>
    <hr class="shop-separator shopvisible" />
<script type="text/javascript">
var shopcategory = "%shopcategory%";
</script>

';

	// defaults to no
	if(empty($entry['extrafields']['item_is_available']) || !$entry['extrafields']['item_is_available'] || $entry['extrafields']['item_is_available']=='no') {
        $entry['extrafields']['item_is_available'] = 'no';
        $entry['extrafields']['item_is_available-no-selected'] = "checked='checked'";
        $entry['extrafields']['item_is_available-yes-selected'] = "";
    } else {
        $entry['extrafields']['item_is_available'] = 'yes';
        $entry['extrafields']['item_is_available-no-selected'] = "";
        $entry['extrafields']['item_is_available-yes-selected'] = "checked='checked'";
    }

	$tax_rates_vat = $PIVOTX['config']->get('shop_tax_rates_vat');
	$tax_rates_vat = explode(',', $tax_rates_vat);
	if(empty($entry['extrafields']['item_tax']) || !$entry['extrafields']['item_tax']) {
		foreach($tax_rates_vat as $rate) {
			$percentage = round($rate*100).'%';
			$tax_options .= '<option value="'.$rate.'">'.$percentage.'</option>';
		}
	} else {
		foreach($tax_rates_vat as $rate) {
			$percentage = round($rate*100).'%';
			if($rate==$entry['extrafields']['item_tax']) {
				$tax_options .= '<option value="'.$rate.'" selected="selected">'.$percentage.'</option>';
			} else {
				$tax_options .= '<option value="'.$rate.'">'.$percentage.'</option>';
			}
		}
	}

    $output = str_replace("%tax_options%", $tax_options, $output);
	
	$shop_category = $PIVOTX['config']->get('shop_category', 'shop');
    $output = str_replace("%shopcategory%", $shop_category, $output);
	
    // Substitute some labels..
    $output = str_replace("%title_item_is_available%", st("Available"), $output);
    $output = str_replace("%desc_item_is_available%", st("If the product is not available, the add to cart button will be disabled."), $output);
	
    $output = str_replace("%label_no%", st("No"), $output);
    $output = str_replace("%label_yes%", st("Yes"), $output);
    $output = str_replace("%title_item_code%", st("Product Code"), $output);
    $output = str_replace("%desc_item_code%", st("SKU, internal product ID or barcode number"), $output);
    $output = str_replace("%title_item_price%", st("Price"), $output);
    $output = str_replace("%extra_item_price%", st("Prices should be given in cents. The shop calculates totals from the price before VAT."), $output);
    $output = str_replace("%title_item_price_excl_tax%", st("Excl."), $output);
    $output = str_replace("%desc_item_price%", st("The price before VAT"), $output);
    $output = str_replace("%title_item_tax%", st("VAT"), $output);
    $output = str_replace("%desc_item_tax%", st("Value Added Tax (VAT) percentage"), $output);
    $output = str_replace("%title_item_price_incl_tax%", st("Incl."), $output);
    $output = str_replace("%desc_item_price_incl_tax%", st("The price after VAT"), $output);
    $output = str_replace("%title_item_product_options%", st("Options"), $output);
    $output = str_replace("%desc_item_product_options%", st("Comma separated list of value::description pairs").' ('.st('example').': <a href="#" class="defaultoptions" rel="extrafield-item_product_options">smlxl</a>)', $output);

    // For ease of use, just try to replace everything in $entry here:
    foreach($entry as $key=>$value) {
        $output = str_replace("%".$key."%", $value, $output);
    }
    foreach($entry['extrafields'] as $key=>$value) {
        $output = str_replace("%".$key."%", $value, $output);
    }

    // Don't keep any %whatever%'s hanging around..
    $output = preg_replace("/%([a-z0-9_-]+)%/i", "", $output);

    $output .= str_replace('[[extensionurl]]', $PIVOTX['paths']['extensions_url'], '
    <script type="text/javascript" src="[[extensionurl]]shop/js/admin.shop.js"></script>');
    $output .= str_replace('[[extensionurl]]', $PIVOTX['paths']['extensions_url'], '
    <style type="text/css">
        @import url([[extensionurl]]shop/css/admin.shop.css);
    </style>');

    return $output;

}