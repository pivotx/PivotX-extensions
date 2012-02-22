<?php
// - Extension: Publish Preview
// - Version: 1.0
// - Author: Two Kings
// - Email: lodewijk@twokings.nl
// - Site: http://www.twokings.nl
// - Description: An extension that makes it possible to send someone a previewlink for unpublished entries
// - Date: 2011-05-26
// - Identifier: publishpreview

/**
 * Extra fields to edit the password..
 */
$this->addHook(
    'in_pivotx_template',
    'entry-code-after',
    array('callback' => 'publishpreviewBlockEntry' )
    );

/**
 * Extra fields to edit the password..
 */
$this->addHook(
    'in_pivotx_template',
    'page-code-after',
    array('callback' => 'publishpreviewBlockPage' )
    );

function publishpreviewBlockEntry($item) {
	$item['itemtype'] = 'entry';
	return publishpreviewBlock($item);
}

function publishpreviewBlockPage($item) {
	$item['itemtype'] = 'page';
	return publishpreviewBlock($item);
}

function publishpreviewBlock($item) {
	global $PIVOTX;

	if($item['status']=="publish") {
		$output = <<< EOM
    <tr><td valign="top" colspan="2"><hr size="1" noshade="noshade" />
		<small>%label0%</small>
        </td>
    </tr>
EOM;

		// Substitute some labels..
		$output = str_replace("%label0%", __("Preview link is only available for unpublished items."), $output);
		return $output;
	}
	
	//debug_printr($PIVOTX['config']);

    $output = <<< EOM
    <tr><td valign="top" colspan="2"><hr size="1" noshade="noshade" /></td></tr>
    <tr>
        <td valign="top" colspan="2">
			<strong>%label1%:</strong>
            <a href="%publishpreviewlink%" target="_blank">%label2%</a>
			<br />
			<input id="publishpreviewcode" value="%publishpreviewlink%" type="text" />
			<br />
            <small>%label3%</small>
        </td>
    </tr>
EOM;

    // Substitute some labels..
    $output = str_replace("%label1%", __("Publish preview"), $output);
    $output = str_replace("%label2%", __("Preview"), $output);
    $output = str_replace("%label3%", __("Use this link if you want send someone a preview link."), $output);

	//debug_printr($item);

    switch($item['itemtype']) {
		case 'entry':
		    $show = 'e';
		    $code = $item['code'];
		    break;
		case 'page':
		default:
		    $show = 'p';
		    $code = $item['uri'];
		    break;
	}
	
	if($code!='') {
		$spamkey = $PIVOTX['config']->get('server_spam_key');
		
		$codetokens = array($spamkey, $code);
		debug_printr($codetokens);
		$previewtoken = publishpreviewCode($codetokens);
		$url = $PIVOTX['paths']['canonical_host'].$PIVOTX['paths']['site_url'];
	
		$output = str_replace("%publishpreviewlink%", $url.'?'.$show.'='.$code.'&amp;previewtoken='.$previewtoken, $output);
	
		// For ease of use, just try to replace everything in $item here:
		foreach($item as $key=>$value) {
			$output = str_replace("%".$key."%", $value, $output);
		}
		foreach($item['extrafields'] as $key=>$value) {
			$output = str_replace("%".$key."%", $value, $output);
		}
		
		// Don't keep any %whatever%'s hanging around..
		$output = preg_replace("/%([a-z0-9_-]+)%/i", "", $output);
	
		return $output;
	} else {
		$output = <<< EOM
    <tr><td valign="top" colspan="2"><hr size="1" noshade="noshade" />
			<small>%label4%</small>
        </td>
    </tr>
EOM;

		// Substitute some labels..
		$output = str_replace("%label4%", __("Preview link will be available for after you save."), $output);
		return $output;
	}
}


// Add a hook to process the fields before saving. Uses the same hook for Pages and Entries
$this->addHook(
    'entry_edit_beforesave',
    'callback',
    'publishpreviewBeforeSave'
);

$this->addHook(
    'page_edit_beforesave',
    'callback',
    'publishpreviewBeforeSave'
);

// cleanup the previewcode so it will not be saved
function publishpreviewBeforeSave($item) {
	if(!empty($item['publishpreviewcode'])) {
		unset($item['publishpreviewcode']);
	}
	return;
}

/**
 * Create a simple token
 */
function publishpreviewCode ($tokens = array()) {
	return md5(join('-',$tokens));
}

$this->addHook(
    'before_parse',
    'callback',
    'publishpreviewPrepare'
);

/**
 * Determine if a token must be checked
 */
function publishpreviewPrepare(&$params) {

    //if(!isset($_GET['previewtoken'])) {return;}
    if ( !defined('PIVOTX_INWEBLOG') || (!isset($_GET['previewtoken'])) ) {
		//debug('publishpreviewPrepare 1 - abort, no token');
        return;
    }
    
	//debug('publishpreviewPrepare 3');
	$valid_token = publishpreviewTestToken($params, $_GET['previewtoken']);
	if($valid_token) {
		//debug('publishpreviewPrepare 2 - token is valid');
		if($params['pagetype']=='entry') {
			$_GET['previewentry'] = true;
		} elseif($params['pagetype']=='page') {
			$_GET['previewpage'] = true;
		}
	} else {
		//debug('publishpreviewPrepare 3 - token is invalid');
		return;
	}
	//debug('publishpreviewPrepare 4 - finish');

}

/**
 * Test it the given token is valid for a page or an entry
 */
function publishpreviewTestToken($params, $token) {
	global $PIVOTX;
	
	//debug_printr($params);
	//debug('publishpreviewTestToken 1:' . $token);
	
	$spamkey = $PIVOTX['config']->get('server_spam_key');

	if($params['pagetype']=='entry') {
    	//debug('publishpreviewTestToken entry');
	    $item = $PIVOTX['db']->read_entry($params['entry']);

        if($item['status']!='publish') {
	
			$codetokens = array($spamkey, $item['uid']);
	        //debug_printr($codetokens);
			$previewtoken = publishpreviewCode($codetokens);
		} else {
			debug('publishpreviewTestToken given, but entry is published');
			return false;
		}
	} elseif($params['pagetype']=='page') {
    	//debug('publishpreviewTestToken page');
        $item = $PIVOTX['pages']->getPageByUri($params['uri']);
        if($item['status']!='publish') {
			
	        $codetokens = array($spamkey, $item['uri']);
	        //debug_printr($codetokens);
			$previewtoken = publishpreviewCode($codetokens);
		} else {
			//debug('publishpreviewTestToken given, but page is published');
			return false;
		}
	} else {
		//debug('publishpreviewTestToken wrong type');
		return false;
	}

	if($previewtoken==$token) {
		//debug('publishpreviewTestToken match:'. $previewtoken);
        $_POST = $item;
		return true;
	}

	//debug('publishpreviewTestToken does not match:'. $previewtoken);
	return false;
}

?>
