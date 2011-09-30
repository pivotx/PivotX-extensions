<?php
/**
 * Installs and updates the database and configuration stuff for the shop
 *
 * FIXME: This needs to be bulletproofed, currently when it fails you have to
 *        edit the configuration and database by hand
 */

if($PIVOTX['config']->get('shop_enabled')!=false) {
    /**
     * Add a hook to global PivotX init
     */
    $this->addHook(
        'after_initialize',
        'callback',
        '_shop_checkupgrades'
        );
}

/**
 * check version and update only if neccesary
 */
function _shop_checkupgrades() {
    global $PIVOTX;
    if(SHOP_VERSION > $PIVOTX['config']->get('shop_version') && function_exists('shopInstallerCheckTables')) {
        shopInstallerCheckTables();            
    }
}

/**
 * Check if database table for Shop exists or if it is the right version.
 *
 * If the version is not right create or update the tables
 */
function shopInstallerCheckTables() {
    global $PIVOTX;

    $currentversion = $PIVOTX['config']->get('shop_version');

    if(empty($currentversion)) {
        // install the most recent version
        // this will not be done when the extension is already installed
        if($result = shopInstallerInstallTables()) {
            $PIVOTX['config']->set('shop_version', SHOP_VERSION);
            debug('installed Shop tables version '. SHOP_VERSION);
        } else {
            debug('installation of Shop tables went wrong - please create or modify your database manually');
        }
    } elseif(is_numeric($currentversion)) {
        // run all update functions from the current version to the most recent version
        for($i = $currentversion+1; $i <= SHOP_VERSION; $i++) {
            $updatefunction = 'shopInstallerUpdateTables_'.$i;
            if(function_exists($updatefunction)) {
                if($result = $updatefunction()) {
                    $PIVOTX['config']->set('shop_version', $i);
                    debug('updated Shop tables to version '.$i);
                } else {
                    debug('update of Shop tables went wrong for update '.$i.' - please create or modify your database manually');
                }
            } else {
                $PIVOTX['config']->set('shop_version', $i);
                debug('updated Shop tables to version '.$i);
                debug('no update needed for Shop version '.$i);   
            }
        }
    } else {
        debug('Shop configuration error');   
    }
    
    // run configurationtest
    $result = shopConfigTest();
}

function shopConfigTest() {
    global $PIVOTX;
    $logmessage = $PIVOTX['config']->get('shop_last_errors');
    $wrong_config = false;
    
	$shop_use_payment = $PIVOTX['config']->get('shop_use_payment');
	$hasmollie = (stristr($shop_use_payment, 'mollie'))?1:0;

	if($hasmollie) {
        $idealfile = dirname(dirname(__FILE__))."/providers/mollie/ideal-php5/ideal.class.php";

        if (!file_exists($idealfile)) {
            $wrong_config = true;
            $logmessage .= '|mollie ideal class missing';
            //debug('mollie ideal class loaded');
        }
    
        if (!in_array('ssl', stream_get_transports())) {
            $wrong_config = true;
            $logmessage .= '|ssl stream support missing';
        }
        
        // test data or unknown mollie config
        if($PIVOTX['config']->get('shop_mollie_partner_key')=='test'
           || $PIVOTX['config']->get('shop_mollie_profile_key')=='') {
            $wrong_config = true;
            $logmessage .= '|Mollie keys are not set';
        }
        
        // mollie and not euro
        if($PIVOTX['config']->get('shop_currency')!=='EUR') {
            $wrong_config = true;
            $logmessage .= '|Mollie requires &euro; currency';
        }
    }
    
    // mailtemplates
    $mailtemplates = array(
		'ideal_return_tpl' => 'shop_email_ideal_return_tpl',
		'other_return_tpl' => 'shop_email_other_return_tpl'
	);
	
    foreach($mailtemplates as $key => $value) {
        $templatename = dirname(dirname(__FILE__)) .'/'. $PIVOTX['config']->get($mailtemplates[$key]);
        //debug('attempting '.  $templatename . ' ...');
        if(!file_exists($templatename)) {
            $wrong_config = true;
            $logmessage .= '|'.$key.' template is not found';
        }
    }

    // If we've set the hidden config option for 'never_jquery', just return without doing anything.
    if ($PIVOTX['config']->get('never_jquery') == 1) {
        $logmessage .= '|never_jquery is on';
    }
    
    // disable the shop if there is anything fatally wrong
    if($wrong_config) {
        $PIVOTX['config']->set('shop_enabled', false);
        $PIVOTX['config']->set('shop_last_errors', $logmessage);
    }

    return true;
}

/**
 * Create basic Shop database table
 *
 * This should always reflect the latest version
 */
function shopInstallerInstallTables() {
    global $PIVOTX;

    // don't load the ShopSql object yet, because it's not initialized yet 
    $prefix = $PIVOTX['config']->get('db_prefix');

    $db = new Sql('mysql', $PIVOTX['config']->get('db_databasename'), $PIVOTX['config']->get('db_hostname'),
                $PIVOTX['config']->get('db_username'), $PIVOTX['config']->get('db_password'));
    $db->query("SHOW TABLES LIKE '" . $PIVOTX['config']->get('db_prefix') . "%'");
    $tables = $db->fetch_all_rows('no_names');
    $tables = make_valuepairs($tables, '', '0');
    
    $shoptable = $prefix.'order';
    $shopitemtable = $prefix.'order_item';
    $shoplogtable = $prefix.'order_log';

    if (!in_array($shoptable, $tables)) {
        $queries[] = "CREATE TABLE IF NOT EXISTS ".$shoptable." (
            order_id int(10) NOT NULL auto_increment,
            order_public_code mediumtext NOT NULL,
            order_public_hash mediumtext NOT NULL,
            order_status enum('unknown', 'initialized', 'waiting', 'complete', 'error', 'cancelled', 'refunded', 'saved', 'expired', 'junk') default 'unknown',
            order_datetime timestamp NOT NULL,
            user_name text NULL,
            user_email text NOT NULL,
            user_phone text NULL,
            user_address text NULL,
            shipping_handler text NULL,
            shipping_external_code text NULL,
            shipping_status text NULL,
            shipping_datetime timestamp NULL,
            payment_provider text NULL,
            payment_external_code text NULL,
            payment_status text NULL,
            payment_datetime timestamp NULL,
            payment_amount_total text NULL,
            payment_message text NULL,            
            PRIMARY KEY (order_id)
        );";

        $queries[] = "ALTER TABLE ".$shoptable." CHANGE order_datetime order_datetime timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ";
        $queries[] = "ALTER TABLE ".$shoptable." ADD INDEX user_email (user_email (8))";
        $queries[] = "ALTER TABLE ".$shoptable." ADD COLUMN user_browser mediumtext NOT NULL AFTER user_address";
        $queries[] = "ALTER TABLE ".$shoptable." ADD COLUMN user_hostname mediumtext NOT NULL AFTER user_address";
        $queries[] = "ALTER TABLE ".$shoptable." ADD COLUMN user_ip mediumtext NOT NULL AFTER user_address";
		
		$queries[] = "ALTER TABLE ".$shoptable." ADD COLUMN user_postcode TINYTEXT NOT NULL AFTER user_address";
		$queries[] = "ALTER TABLE ".$shoptable." ADD COLUMN user_city TINYTEXT NOT NULL AFTER user_postcode";
		$queries[] = "ALTER TABLE ".$shoptable." ADD COLUMN user_country TINYTEXT NOT NULL AFTER user_city";
        
        $queries[] = "CREATE TABLE IF NOT EXISTS ".$shopitemtable." (
            order_item_id int(10) NOT NULL auto_increment,
            order_id int(10) NOT NULL,
            order_datetime timestamp NOT NULL,
            order_no_items text NULL,     
            item_code text NULL,
            item_title text NOT NULL,
            item_content text NULL,
            item_price text NULL,
            item_price_incl_tax text NULL,
            item_tax_percentage text NULL,       
            PRIMARY KEY (order_item_id)
        );";
        $queries[] = "ALTER TABLE ".$shopitemtable." CHANGE order_datetime order_datetime timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ";
        $queries[] = "ALTER TABLE ".$shopitemtable." ADD COLUMN item_id int(10) NOT NULL AFTER order_no_items";
        $queries[] = "ALTER TABLE ".$shopitemtable." ADD COLUMN item_option text NULL AFTER item_id";
    
        $queries[] = "CREATE TABLE IF NOT EXISTS ".$shoplogtable." (
            order_log_id int(10) NOT NULL auto_increment,
            order_id int(10) NOT NULL,
            log_datetime timestamp NOT NULL,
            log_type text NULL,
            log_severity text NULL,
            log_message text NULL,       
            PRIMARY KEY (order_log_id)
        );";

        $queries[] = "ALTER TABLE ".$shoplogtable." CHANGE log_datetime log_datetime timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ";

        $queries[] = "ALTER TABLE ".$shoptable." DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";
        $queries[] = "ALTER TABLE ".$shopitemtable." DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";
        $queries[] = "ALTER TABLE ".$shoplogtable." DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";
    
        $errors = array();
        foreach($queries as $query) {
            if(!($result = $db->query($query))) {
                $errors[] = $db->get_last_query();
            }
            
        }
    
        if(!empty($errors)) {
            debug_printr($errors);
            return false;
        }

        // all database tables are completed, now also set all variables
        $email = getDefault($_SERVER['SERVER_ADMIN'], 'info@'.$_SERVER['HTTP_HOST']);
        $PIVOTX['config']->set('shop_email_address', $email);
        $PIVOTX['config']->set('shop_email_name', 'Shop');
        $PIVOTX['config']->set('shop_language', 'default');
        $PIVOTX['config']->set('shop_currency', 'EUR');
        $PIVOTX['config']->set('shop_tax_rates_vat', '0.19,0.06,0');
        $PIVOTX['config']->set('shop_tax_rates_sales', '0');
        $PIVOTX['config']->set('shop_mollie_partner_key', 'test');
        $PIVOTX['config']->set('shop_mollie_profile_key', 'test');
        $PIVOTX['config']->set('shop_mollie_testmode', true);
        $PIVOTX['config']->set('shop_mollie_return_url', 'index.php?action=return');
        $PIVOTX['config']->set('shop_mollie_report_url', 'index.php?action=report');
        $PIVOTX['config']->set('shop_automatic', '0');
        $PIVOTX['config']->set('shop_category', 'shop');
        $PIVOTX['config']->set('shop_use_shipping', 'fixed');
        $PIVOTX['config']->set('shop_shipping_fixed_amount', '500');
        $PIVOTX['config']->set('shop_shipping_tax_rate', '0.19');
        $PIVOTX['config']->set('shop_use_payment', 'no');
        $PIVOTX['config']->set('shop_builtin_css', true);
        $PIVOTX['config']->set('shop_default_template', 'checkout.tpl');
        $PIVOTX['config']->set('shop_default_theme', 'skinny');
        $PIVOTX['config']->set('shop_email_ideal_return_tpl', 'templates/email_order_ideal_return.tpl');
        $PIVOTX['config']->set('shop_email_other_return_tpl', 'templates/email_order_other_payment_provider_return.tpl');

        // this might be a nice function to have :)
        $PIVOTX['config']->set('shop_enabled', true);
        $PIVOTX['config']->set('shop_last_errors', '');
        $PIVOTX['config']->set('shop_default_homepage', '/index.php?c=shop');
        
        // sizes and variants
        $PIVOTX['config']->set('shop_product_variants', 'none');

        // oops settings
        $PIVOTX['config']->set('oops_table_'.$prefix.'order_active', 'on');
        $PIVOTX['config']->set('oops_table_'.$prefix.'order_name', 'order');
        
        $PIVOTX['config']->set('oops_table_'.$prefix.'order_item_active', 'on');
        $PIVOTX['config']->set('oops_table_'.$prefix.'order_item_name', 'order_item');
        
        $PIVOTX['config']->set('oops_table_'.$prefix.'order_log_active', 'on');
        $PIVOTX['config']->set('oops_table_'.$prefix.'order_log_name', 'order_log');
        
        // run text suggestions for oops
        shopInstallerOopsSuggestions();
        
        // we see no errors, so lets just pretend that worked
        return true;

    }

    // something went wrong - the table probably exists
    return false;
}

/**
 * Run text suggestions for oops in the back-end
 */
function shopInstallerOopsSuggestions() {

    global $PIVOTX;

    $instance = oops_text::instance(); 
    //$instance->suggest_term($key, $term, $language);
    $group = 'oops_tableedit.order.';
    $instance->suggest_term($group.'add_record', 'toevoegen', 'nl');
    $instance->suggest_term($group.'back_to_records', 'terug', 'nl');
    $instance->suggest_term($group.'from_N_pages', 'van %d', 'nl');
    $instance->suggest_term($group.'no_of', '%d orders', 'nl');
    $instance->suggest_term($group.'order_id', 'Order ID', 'nl');
    $instance->suggest_term($group.'order_public_code', 'Publieke code', 'nl');
    $instance->suggest_term($group.'order_public_hash', 'Publieke hash', 'nl');
    $instance->suggest_term($group.'order_datetime', 'Datum', 'nl');
    $instance->suggest_term($group.'order_status', 'Status', 'nl');
    $instance->suggest_term($group.'orderitems', 'Items', 'nl');
    $instance->suggest_term($group.'page', 'Pagina', 'nl');
    $instance->suggest_term($group.'payment_amount_total', 'Totaalbedrag', 'nl');
    $instance->suggest_term($group.'payment_datetime', 'Betaaldatum', 'nl');
    $instance->suggest_term($group.'payment_external_code', 'Externe betalingscode', 'nl');
    $instance->suggest_term($group.'payment_message', 'Betalings berichten', 'nl');
    $instance->suggest_term($group.'payment_provider', 'Provider', 'nl');
    $instance->suggest_term($group.'payment_status', 'Betaal status', 'nl');
    $instance->suggest_term($group.'reset-search', 'Reset', 'nl');
    $instance->suggest_term($group.'search', 'Zoeken', 'nl');
    $instance->suggest_term($group.'shipping_datetime', 'Verzenddatum', 'nl');
    $instance->suggest_term($group.'shipping_external_code', 'Externe verzendcode', 'nl');
    $instance->suggest_term($group.'shipping_handler', 'Verzendbedrijf', 'nl');
    $instance->suggest_term($group.'shipping_status', 'Verzendstatus', 'nl');
    $instance->suggest_term($group.'user_address', 'Adres', 'nl');
    $instance->suggest_term($group.'user_browser', 'Browser', 'nl');
    $instance->suggest_term($group.'user_email', 'Email', 'nl');
    $instance->suggest_term($group.'user_hostname', 'Hostnaam', 'nl');
    $instance->suggest_term($group.'user_ip', 'IP adres', 'nl');
    $instance->suggest_term($group.'user_name', 'Naam', 'nl');
    $instance->suggest_term($group.'user_phone', 'Telefoonnummer', 'nl');
}

/**
 * Update tables to version 2
 */
function shopInstallerUpdateTables_2() {
    global $PIVOTX;

    // all database tables are completed, now also set all variables
    $email = getDefault($_SERVER['SERVER_ADMIN'], 'info@'.$_SERVER['HTTP_HOST']);
    $PIVOTX['config']->set('shop_email_address', $email);
    $PIVOTX['config']->set('shop_email_name', 'Shop');
    $PIVOTX['config']->set('shop_mollie_partner_key', 'test');
    $PIVOTX['config']->set('shop_mollie_profile_key', 'test');
    $PIVOTX['config']->set('shop_currency', 'EUR');
    $PIVOTX['config']->set('shop_tax_rates_vat', '0.19,0.06,0');
    $PIVOTX['config']->set('shop_tax_rates_sales', '0');
    // we see no errors, so lets just pretend that worked
    return true;

}


/**
 * Update tables to version 3
 */
function shopInstallerUpdateTables_3() {
    global $PIVOTX;

    // update table queries

    // don't load the ShopSql object yet, because it's not initialized yet 
    $prefix = $PIVOTX['config']->get('db_prefix');

    $db = new Sql('mysql', $PIVOTX['config']->get('db_databasename'), $PIVOTX['config']->get('db_hostname'),
                $PIVOTX['config']->get('db_username'), $PIVOTX['config']->get('db_password'));
    $db->query("SHOW TABLES LIKE '" . $PIVOTX['config']->get('db_prefix') . "%'");
    $tables = $db->fetch_all_rows('no_names');
    $tables = make_valuepairs($tables, '', '0');
    
    $shoptable = $prefix.'order';
    $shopitemtable = $prefix.'order_item';

    if (!in_array($shoptable, $tables)) {
        $queries[] = "CREATE TABLE IF NOT EXISTS ".$shoptable." (
            order_id int(10) NOT NULL auto_increment,
            order_public_code mediumtext NOT NULL,
            order_public_hash mediumtext NOT NULL,
            order_status enum('unknown','initialized','waiting','complete','error','cancelled','saved','expired','junk') default 'unknown',
            order_datetime timestamp NOT NULL,
            user_name text NULL,
            user_email text NOT NULL,
            user_phone text NULL,
            user_address text NULL,
            shipping_handler text NULL,
            shipping_external_code text NULL,
            shipping_status text NULL,
            shipping_datetime timestamp NULL,
            payment_provider text NULL,
            payment_external_code text NULL,
            payment_status text NULL,
            payment_datetime timestamp NULL,
            payment_amount_total text NULL,
            payment_message text NULL,            
            PRIMARY KEY (order_id)
        );";

        $queries[] = "ALTER TABLE ".$shoptable." CHANGE order_datetime order_datetime timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ";
        $queries[] = "ALTER TABLE ".$shoptable." ADD INDEX user_email (user_email (8))";

        $queries[] = "ALTER TABLE ".$shoptable." ADD COLUMN user_browser mediumtext NOT NULL AFTER user_address";
        $queries[] = "ALTER TABLE ".$shoptable." ADD COLUMN user_hostname mediumtext NOT NULL AFTER user_address";

        $queries[] = "CREATE TABLE IF NOT EXISTS ".$shopitemtable." (
            order_item_id int(10) NOT NULL auto_increment,
            order_id int(10) NOT NULL,
            order_datetime timestamp NOT NULL,
            order_no_items text NULL,     
            item_code text NULL,
            item_title text NOT NULL,
            item_content text NULL,
            item_price text NULL,
            item_price_incl_tax text NULL,
            item_tax_percentage text NULL,       
            PRIMARY KEY (order_item_id)
        );";
        $queries[] = "ALTER TABLE ".$shopitemtable." CHANGE order_datetime order_datetime timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ";
    
        $errors = array();
        foreach($queries as $query) {
            if(!($result = $db->query($query))) {
                $errors[] = $db->get_last_query();
            }            
        }
    
        if(!empty($errors)) {
            debug_printr($errors);
            return false;
        }
    }
    // we see no errors, so lets just pretend that worked
    return true;
}

/**
 * Update tables to version 4
 */
function shopInstallerUpdateTables_4() {
    global $PIVOTX;

    // update table queries


    // don't load the ShopSql object yet, because it's not initialized yet 
    $prefix = $PIVOTX['config']->get('db_prefix');

    $db = new Sql('mysql', $PIVOTX['config']->get('db_databasename'), $PIVOTX['config']->get('db_hostname'),
                $PIVOTX['config']->get('db_username'), $PIVOTX['config']->get('db_password'));
    $db->query("SHOW TABLES LIKE '" . $PIVOTX['config']->get('db_prefix') . "%'");
    $tables = $db->fetch_all_rows('no_names');
    $tables = make_valuepairs($tables, '', '0');
    
    $shoplogtable = $prefix.'order_log';

    if (!in_array($shoplogtable, $tables)) {
        $queries[] = "CREATE TABLE IF NOT EXISTS ".$shoplogtable." (
            order_log_id int(10) NOT NULL auto_increment,
            order_id int(10) NOT NULL,
            log_datetime timestamp NOT NULL,
            log_type text NULL,
            log_severity text NULL,
            log_message text NULL,       
            PRIMARY KEY (order_log_id)
        );";

        $queries[] = "ALTER TABLE ".$shoplogtable." CHANGE log_datetime log_datetime timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ";

        $errors = array();
        foreach($queries as $query) {
            if(!($result = $db->query($query))) {
                $errors[] = $db->get_last_query();
            }            
        }
    
        if(!empty($errors)) {
            debug_printr($errors);
            return false;
        }
    }
    // we see no errors, so lets just pretend that worked
    return true;
}

/**
 * Update tables to version 5
 */
function shopInstallerUpdateTables_5() {
    global $PIVOTX;

    $PIVOTX['config']->set('shop_mollie_return_url', 'index.php?action=return');
    $PIVOTX['config']->set('shop_mollie_report_url', 'index.php?action=report');

    // we see no errors, so lets just pretend that worked
    return true;

}

/**
 * Update tables to version 6
 */
function shopInstallerUpdateTables_6() {
    global $PIVOTX;

    $PIVOTX['config']->set('shop_automatic', '1');

    // we see no errors, so lets just pretend that worked
    return true;

}

/**
 * Update tables to version 7
 */
function shopInstallerUpdateTables_7() {
    global $PIVOTX;

    $PIVOTX['config']->set('shop_automatic', '0');
    $PIVOTX['config']->set('shop_category', 'shop');

    // we see no errors, so lets just pretend that worked
    return true;

}

/**
 * Update tables to version 8
 */
function shopInstallerUpdateTables_8() {
    global $PIVOTX;

    $PIVOTX['config']->set('shop_use_shipping', true);
    $PIVOTX['config']->set('shop_use_payment', true);

    // we see no errors, so lets just pretend that worked
    return true;

}

/**
 * Update tables to version 9
 */
function shopInstallerUpdateTables_9() {
    global $PIVOTX;

    // update table queries

    // don't load the ShopSql object yet, because it's not initialized yet 
    $prefix = $PIVOTX['config']->get('db_prefix');

    $db = new Sql('mysql', $PIVOTX['config']->get('db_databasename'), $PIVOTX['config']->get('db_hostname'),
                $PIVOTX['config']->get('db_username'), $PIVOTX['config']->get('db_password'));
    $db->query("SHOW TABLES LIKE '" . $PIVOTX['config']->get('db_prefix') . "%'");
    $tables = $db->fetch_all_rows('no_names');
    $tables = make_valuepairs($tables, '', '0');
    
    $shoptable = $prefix.'order';
    $shopitemtable = $prefix.'order_item';

    $queries[] = "ALTER TABLE ".$shopitemtable." ADD COLUMN item_id int(10) NOT NULL AFTER order_no_items";

    $errors = array();
    foreach($queries as $query) {
        if(!($result = $db->query($query))) {
            $errors[] = $db->get_last_query();
        }            
    }

    if(!empty($errors)) {
        debug_printr($errors);
        return false;
    }

    // we see no errors, so lets just pretend that worked
    return true;

}

/**
 * Update tables to version 10
 */
function shopInstallerUpdateTables_10() {
    global $PIVOTX;

    $PIVOTX['config']->set('shop_builtin_css', true);

    // we see no errors, so lets just pretend that worked
    return true;

}

/**
 * Update tables to version 11
 */
function shopInstallerUpdateTables_11() {
    global $PIVOTX;

    $PIVOTX['config']->set('shop_mollie_testmode', true);
    // whoops, totally forgot this one
    
    return true;
}

/**
 * Update tables to version 12
 */
function shopInstallerUpdateTables_12() {
    global $PIVOTX;

    $PIVOTX['config']->set('shop_email_ideal_return_tpl', 'templates/email_order_ideal_return.tpl');
    $PIVOTX['config']->set('shop_email_other_return_tpl', 'templates/email_order_other_payment_provider_return.tpl');
    $PIVOTX['config']->set('shop_default_theme', 'skinny');
    $PIVOTX['config']->set('shop_default_template', 'checkout.tpl');
    // this might be a nice function to have :)
    $PIVOTX['config']->set('shop_enabled', true);
    // we see no errors, so lets just pretend that worked
    return true;
}

/**
 * Update tables to version 13
 */
function shopInstallerUpdateTables_13() {
    global $PIVOTX;

    $PIVOTX['config']->set('shop_default_homepage', '/index.php?c=shop');
    // we see no errors, so lets just pretend that worked
    return true;
}

/**
 * Update tables to version 15
 */
function shopInstallerUpdateTables_15() {
    global $PIVOTX;

    // update table queries

    // don't load the ShopSql object yet, because it's not initialized yet 
    $prefix = $PIVOTX['config']->get('db_prefix');

    $db = new Sql('mysql', $PIVOTX['config']->get('db_databasename'), $PIVOTX['config']->get('db_hostname'),
                $PIVOTX['config']->get('db_username'), $PIVOTX['config']->get('db_password'));
    $db->query("SHOW TABLES LIKE '" . $PIVOTX['config']->get('db_prefix') . "%'");
    $tables = $db->fetch_all_rows('no_names');
    $tables = make_valuepairs($tables, '', '0');
    
    $shoptable = $prefix.'order';
    $shopitemtable = $prefix.'order_item';

    $queries[] = "ALTER TABLE ".$shoptable." CHANGE order_status order_status enum('unknown', 'initialized', 'waiting', 'complete', 'error', 'cancelled', 'refunded', 'saved', 'expired', 'junk') default 'unknown' ";
    $errors = array();
    foreach($queries as $query) {
        if(!($result = $db->query($query))) {
            $errors[] = $db->get_last_query();
        }            
    }

    if(!empty($errors)) {
        debug_printr($errors);
        return false;
    }    
    // we see no errors, so lets just pretend that worked
    return true;
}

/**
 * Update tables to version 17
 */
function shopInstallerUpdateTables_17() {
    global $PIVOTX;

    $PIVOTX['config']->set('shop_use_payment', 'no');
    // we see no errors, so lets just pretend that worked
    return true;
}


/**
 * Update tables to version 18
 */
function shopInstallerUpdateTables_18() {
    global $PIVOTX;

    // update table queries

    // don't load the ShopSql object yet, because it's not initialized yet 
    $prefix = $PIVOTX['config']->get('db_prefix');

    $db = new Sql('mysql', $PIVOTX['config']->get('db_databasename'), $PIVOTX['config']->get('db_hostname'),
                $PIVOTX['config']->get('db_username'), $PIVOTX['config']->get('db_password'));
    $db->query("SHOW TABLES LIKE '" . $PIVOTX['config']->get('db_prefix') . "%'");
    $tables = $db->fetch_all_rows('no_names');
    $tables = make_valuepairs($tables, '', '0');
    
    $shoptable = $prefix.'order';
    $shopitemtable = $prefix.'order_item';
    $shoplogtable = $prefix.'order_log';

    $queries[] = "ALTER TABLE ".$shoptable." DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";
    $queries[] = "ALTER TABLE ".$shopitemtable." DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";
    $queries[] = "ALTER TABLE ".$shoplogtable." DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";
    
    $errors = array();
    foreach($queries as $query) {
        if(!($result = $db->query($query))) {
            $errors[] = $db->get_last_query();
        }            
    }

    if(!empty($errors)) {
        debug_printr($errors);
        return false;
    }

    // we see no errors, so lets just pretend that worked
    return true;

}

/**
 * Update tables to version 19
 */
function shopInstallerUpdateTables_19() {
    global $PIVOTX;

    $PIVOTX['config']->set('shop_use_shipping', false);
    $PIVOTX['config']->set('shop_shipping_fixed_amount', '500');
    $PIVOTX['config']->set('shop_shipping_tax_rate', '0.19');

    // we see no errors, so lets just pretend that worked
    return true;
}


/**
 * Update tables to version 21
 */
function shopInstallerUpdateTables_21() {
    global $PIVOTX;

    $PIVOTX['config']->set('shop_last_errors', '');

    // we see no errors, so lets just pretend that worked
    return true;
}



/**
 * Update tables to version 26
 */
function shopInstallerUpdateTables_26() {
    global $PIVOTX;
    
    $prefix = $PIVOTX['config']->get('db_prefix');

    // oops settings
    $PIVOTX['config']->set('oops_table_'.$prefix.'order_active', 'on');
    $PIVOTX['config']->set('oops_table_'.$prefix.'order_name', 'order');
    
    $PIVOTX['config']->set('oops_table_'.$prefix.'order_item_active', 'on');
    $PIVOTX['config']->set('oops_table_'.$prefix.'order_item_name', 'order_item');
    
    $PIVOTX['config']->set('oops_table_'.$prefix.'order_log_active', 'on');
    $PIVOTX['config']->set('oops_table_'.$prefix.'order_log_name', 'order_log');

    // we see no errors, so lets just pretend that worked
    return true;
}

/**
 * Update tables to version 27
 */
function shopInstallerUpdateTables_27() {
    global $PIVOTX;

    $PIVOTX['config']->set('shop_product_variants', 'none');

    // update table queries

    // don't load the ShopSql object yet, because it's not initialized yet 
    $prefix = $PIVOTX['config']->get('db_prefix');

    $db = new Sql('mysql', $PIVOTX['config']->get('db_databasename'), $PIVOTX['config']->get('db_hostname'),
                $PIVOTX['config']->get('db_username'), $PIVOTX['config']->get('db_password'));
    $shopitemtable = $prefix.'order_item';

    $queries[] = "ALTER TABLE ".$shopitemtable." ADD COLUMN item_option text NULL AFTER item_id";
    
    $errors = array();
    foreach($queries as $query) {
        if(!($result = $db->query($query))) {
            $errors[] = $db->get_last_query();
        }            
    }

    if(!empty($errors)) {
        debug_printr($errors);
        return false;
    }

    // we see no errors, so lets just pretend that worked
    return true;

}

/**
 * Update tables to version 29
 */
function shopInstallerUpdateTables_29() {
    shopInstallerOopsSuggestions();
    
    // we see no errors, so lets just pretend that worked
    return true;
}
/**
 * Update tables to version 33
 */
function shopInstallerUpdateTables_33() {
    global $PIVOTX;

    $PIVOTX['config']->set('shop_product_variants', 'none');

    // update table queries

    // don't load the ShopSql object yet, because it's not initialized yet 
    $prefix = $PIVOTX['config']->get('db_prefix');

    $db = new Sql('mysql', $PIVOTX['config']->get('db_databasename'), $PIVOTX['config']->get('db_hostname'),
                $PIVOTX['config']->get('db_username'), $PIVOTX['config']->get('db_password'));
    $shoptable = $prefix.'order';

	$queries[] = "ALTER TABLE ".$shoptable." ADD COLUMN user_postcode TINYTEXT NOT NULL AFTER user_address";
	$queries[] = "ALTER TABLE ".$shoptable." ADD COLUMN user_city TINYTEXT NOT NULL AFTER user_postcode";
	$queries[] = "ALTER TABLE ".$shoptable." ADD COLUMN user_country TINYTEXT NOT NULL AFTER user_city";
    
    $errors = array();
    foreach($queries as $query) {
        if(!($result = $db->query($query))) {
            $errors[] = $db->get_last_query();
        }            
    }

    if(!empty($errors)) {
        debug_printr($errors);
        return false;
    }

    // we see no errors, so lets just pretend that worked
    return true;

}


/* ****

REMEMBER TO UPDATE THE shopInstallerInstallTables
FUNCTION WITH ALL QUERIES AND VARIABLES YOU ADDED IN ANY UPDATE FUNCTIONS

**** */