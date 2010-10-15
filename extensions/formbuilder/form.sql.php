<?php

/**
 * Class to work with Formbuilder Log, using the sql storage model.
 *
 */
class FormbuilderLogSql {

	/**
	 * Initialisation.
	 *
	 * @return FormbuilderLogSql
	 */
	function FormbuilderLogSql() {
		global $PIVOTX;
        
        if($PIVOTX['config']->data['formbuilderlog_version']<1) {
            formbuilderlogCheckTables();            
        }
        
		// Set the names for the tables we use.
		$this->formbuilderlogtable = safe_string($PIVOTX['config']->get('db_prefix')."formbuilder_log", true);

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
	 * Get a single Formbuilder Log by its uid
	 *
	 * @param integer $formbuilderlog_uid
	 * @return array
	 */
	function getFormbuilderLog($formbuilderlog_uid) {
		$qry = array();
		$qry['select'] = "*";
		$qry['from'] = $this->formbuilderlogtable;
		$qry['where'][] = "formbuilderlog_uid=" . $formbuilderlog_uid;
		$tmpquery = $this->sql->build_select($qry);
		
		//debug("getFormbuilderLog\n" . $tmpquery);
		
		$this->sql->query();
		$formbuildercache = $this->sql->fetch_row();
		return $formbuildercache;

	}

	/**
	 * Delete a single Formbuilder Log
	 *
	 * @param integer $formbuilderlog_uid
	 */
	function delFormbuilderLog($formbuilderlog_uid) {
		$qry = array();
		$qry['delete'] = $this->formbuilderlogtable;
		$qry['where'] = "formbuilderlog_uid=" . $formbuilderlog_uid;
		$tmpquery = $this->sql->build_delete($qry);

		//debug("delFormbuilderLog\n"  . $tmpquery);

		$this->sql->query();
	}
	
	/**
	 * Save a single Formbuilder Log
	 * Create it when it does not exist yet
	 *
	 * @param array $submission
	 * @return int
	 */
	function saveFormbuilderLog($submission) {
		$value = array(
			'formbuilderlog_uid' => $submission['formbuilderlog_uid'],
			'form_id' => $submission['form_id'],
			'submission_id' => $submission['submission_id'],
			'last_updated' => ($submission['last_updated'])?$submission['last_updated']:date("Y-m-d H:i:s", time()),
			'response' => $submission['response'],
			'status' => ($submission['status'])?$submission['status']:'new',
            'user_email' => $submission['user_email'],
            'user_name' => $submission['user_name'],
            'user_ip' => $submission['user_ip'],
            'user_hostname' => $submission['user_hostname'],
            'user_browser' => $submission['user_browser'],
            'form_fields' => $submission['form_fields'],
            'form_values' => $submission['form_values']
		);

		if ($submission['formbuilderlog_uid']=="" || $submission['formbuilderlog_uid']==null) {
			// New cache item
			$qry=array();
			$qry['into'] = $this->formbuilderlogtable;
			$qry['value'] = $value;
			$tmpquery = $this->sql->build_insert($qry);

			//debug("saveFormbuilderLog new\n"  . $tmpquery);

			$this->sql->query();
			$log_uid = $this->sql->get_last_id();
		} else {
			$qry=array();
			$qry['update'] = $this->formbuilderlogtable;
			$qry['value'] = $value;
			$qry['where'] = "formbuilderlog_uid='" . $submission['formbuilderlog_uid'] . "'";
			$tmpquery = $this->sql->build_update($qry);

			//debug("saveFormbuilderLog existing\n" . $tmpquery);

			$this->sql->query();
		}
		// Return the uid of the page we just inserted / updated..
		return $formbuilder['formbuilderlog_uid'];
	}
}


/**
 * Check if database table for Formbuilder Log exists, otherwise create it
 * run other update functions ans set variables
 */
function formbuilderlogCheckTables() {
    global $PIVOTX;

    if($PIVOTX['config']->data['formbuilderlog_version']<1) {
        $result = formbuilderlogInstallTables();
        if($result) {
            $PIVOTX['config']->set('formbuilderlog_version', '1');
            debug('installed Formbuilder Log tables');
        } else {
            debug('installation of Formbuilder Log tables went wrong - please create or modify your database manually');
        }
    }

    if ($PIVOTX['config']->data['formbuilderlog_version'] > 0) {
        if($PIVOTX['config']->data['formbuilderlog_version'] < 2) {
            $PIVOTX['config']->set('formbuilderlog_version', '2');
            $PIVOTX['config']->set('formbuilder_default_email', '');
            $PIVOTX['config']->set('formbuilder_default_name', '');
            debug('updated Formbuilder Log tables to version 2, installed default values');
        }

        if($PIVOTX['config']->data['formbuilderlog_version'] < 3) {
            // stuff to do for the next update
            $x = formbuilderlogUpdateTables_3();
            $PIVOTX['config']->set('formbuilderlog_version', '3');
			debug('updated Formbuilder Log tables to version 3');
        }


    }
}

/**
 * Create basic Formbuilder Log database table
 */
function formbuilderlogInstallTables() {
    global $PIVOTX;

    // don't load the FormbuilderLogSql object yet, because it's not initialized yet 
    $formbuilderlogtable = safe_string($PIVOTX['config']->get('db_prefix')."formbuilder_log", true);

    $db = new Sql('mysql', $PIVOTX['config']->get('db_databasename'), $PIVOTX['config']->get('db_hostname'),
                $PIVOTX['config']->get('db_username'), $PIVOTX['config']->get('db_password'));
    $db->query("SHOW TABLES LIKE '" . $PIVOTX['config']->get('db_prefix') . "%'");
    $tables = $db->fetch_all_rows('no_names');
    $tables = make_valuepairs($tables, '', '0');

    if (!in_array($formbuilderlogtable, $tables)) {
        $queries[] = "CREATE TABLE IF NOT EXISTS ".$formbuilderlogtable." (
            formbuilderlog_uid int(10) NOT NULL auto_increment,
            form_id mediumtext NOT NULL,
            submission_id mediumtext NOT NULL,
            last_updated timestamp NOT NULL,
            response text NOT NULL,
            status enum('new', 'saved', 'junk') default 'new',
            PRIMARY KEY (formbuilderlog_uid)
        );";
        $queries[] = "ALTER TABLE ".$formbuilderlogtable." CHANGE last_updated last_updated timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ";
        $queries[] = "ALTER TABLE ".$formbuilderlogtable." ADD INDEX form_id (form_id (8))";

        foreach($queries as $query) {
            $db->query($query);
        }
        return true;
    }

    // something went wrong - the table probably exists
    return false;
}
/**
 * Update tables to version 3
 */
function formbuilderlogUpdateTables_3() {
    global $PIVOTX;

    $default_email = '';
    $default_name = '';

    $PIVOTX['config']->set('formbuilder_default_email', $default_email);
    $PIVOTX['config']->set('formbuilder_default_name', $default_name);

    // update table queries

    // don't load the FormbuilderLogSql object yet, because it's not initialized yet 
    $db = new Sql('mysql', $PIVOTX['config']->get('db_databasename'), $PIVOTX['config']->get('db_hostname'),
                $PIVOTX['config']->get('db_username'), $PIVOTX['config']->get('db_password'));
    $formbuilderlogtable = safe_string($PIVOTX['config']->get('db_prefix')."formbuilder_log", true);
    
    $queries[] = "ALTER TABLE ".$formbuilderlogtable." ADD COLUMN form_values text NULL AFTER last_updated";
    $queries[] = "ALTER TABLE ".$formbuilderlogtable." ADD COLUMN form_fields text NULL AFTER last_updated";
    $queries[] = "ALTER TABLE ".$formbuilderlogtable." ADD COLUMN user_browser mediumtext NOT NULL AFTER last_updated";
    $queries[] = "ALTER TABLE ".$formbuilderlogtable." ADD COLUMN user_hostname mediumtext NOT NULL AFTER last_updated";
    $queries[] = "ALTER TABLE ".$formbuilderlogtable." ADD COLUMN user_ip mediumtext NOT NULL AFTER last_updated";
    $queries[] = "ALTER TABLE ".$formbuilderlogtable." ADD COLUMN user_name mediumtext NOT NULL AFTER last_updated";
    $queries[] = "ALTER TABLE ".$formbuilderlogtable." ADD COLUMN user_email mediumtext NOT NULL AFTER last_updated";
    
    foreach($queries as $query) {
        $db->query($query);
    }

	// lets just pretend that worked
    return true;
}


// Add a hook to the scheduler, to periodically cleanup the Formbuilder Log tables
$this->addHook(
    'scheduler',
    'callback',
    'formbuilderlogSchedulerCallback'
    );

function formbuilderlogSchedulerCallback() {
	global $PIVOTX;
    // check if logfiles are there is okay
    if($PIVOTX['config']->get('db_model') == 'mysql' && $PIVOTX['config']->data['formbuilderlog_version'] > 1) {
	    $formbuilderlog = new FormbuilderLogSql();
	    $lastweek = time() - (7 * 24 * 60 * 60);
	    $queries[] = sprintf("DELETE FROM %s WHERE last_updated < %d AND status = 'junk'", $formbuilderlog->formbuilderlogtable, $lastweek);

	    foreach($queries as $query) {
	        $formbuilderlog->sql->query($query);
	    }
	    debug('cleaned up old entries from the Formbuilder Log table');
		// lets just pretend that worked
	    return true;
    }
}