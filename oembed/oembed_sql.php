<?php

/**
 * Class to work with oembed cache, using the sql storage model.
 *
 */
class OembedCacheSql {

	/**
	 * Initialisation.
	 *
	 * @return OembedCacheSql
	 */
	function OembedCacheSql() {
		global $PIVOTX;

		// Set the names for the tables we use.
		$this->oemcachetable = safe_string($PIVOTX['config']->get('db_prefix')."oembed_cache", true);

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
	 * Get a single page by its uid
	 *
	 * @param integer $uid
	 * @return array
	 */
	function getOembedCache($url, $max_width=false, $max_height=false) {
		$qry = array();
		$qry['select'] = "*";
		$qry['from'] = $this->oemcachetable;
		$qry['where'][] = "source_url='" . $url . "'";
		if($max_width) {
			$qry['where'][] = "max_width='" . $max_width . "'";
		}
        if($max_height) {
			$qry['where'][] = "max_height='" . $max_height . "'";
		}
		$tmpquery = $this->sql->build_select($qry);
		
		//debug("getOembedCache\n" . $tmpquery);
		
		$this->sql->query();
		$oembedcache = $this->sql->fetch_row();
		return $oembedcache;

	}

	/**
	 * Delete a single page
	 *
	 * @param integer $uid
	 */
	function delOembedCache($url) {
		$qry = array();
		$qry['delete'] = $this->oemcachetable;
		$qry['where'] = "source_url='" . $url . "'";
		$tmpquery = $this->sql->build_delete($qry);

		//debug("delOembedCache\n"  . $tmpquery);

		$this->sql->query();
	}
	
	/**
	 * Save a single page
	 *
	 * @param integer $id
	 * @param array $page
	 */
	function saveOembedCache($oembed) {
		$value = array(
			'oembed_uid' => $oembed['oembed_uid'],
			'source_url' => $oembed['source_url'],
			'max_width' => $oembed['max_width'],
			'max_height' => $oembed['max_height'],
			'provider_url' => $oembed['provider_url'],
			'last_updated' => date("Y-m-d H:i:s", time()),
			'response' => $oembed['response'],
			'status' => $oembed['status']
		);

		if ($oembed['oembed_uid']=="" || $oembed['oembed_uid']==null) {
			// New cache item
			$qry=array();
			$qry['into'] = $this->oemcachetable;
			$qry['value'] = $value;
			$tmpquery = $this->sql->build_insert($qry);

			//debug("saveOembedCache new\n"  . $tmpquery);

			$this->sql->query();
			$oembed_uid = $this->sql->get_last_id();
		} else {
			$qry=array();
			$qry['update'] = $this->oemcachetable;
			$qry['value'] = $value;
			$qry['where'] = "source_url='" . $oembed['source_url'] . "'";
			$tmpquery = $this->sql->build_update($qry);

			//debug("saveOembedCache existing\n" . $tmpquery);

			$this->sql->query();
		}
		// Return the uid of the page we just inserted / updated..
		return $oembed['source_url'];
	}
}
