<?php

/**
 * TODO: make the submission work with all the spam stuff
 *
 */

// Register 'orderform' as a smarty tag.
$PIVOTX['template']->register_block('formbuilder', 'smarty_formbuilder');

/**
 * Formbuilder tag
 *
 * The formbuilder tag is a block tag, which means that it always has to
 * have an accompanying closing formbuilder tag.
 * What's inside the tag is used to configure the form
 *
 * @param array $params
 * @param string $format
 * @param object $smarty
 * @return string
 */
function smarty_formbuilder($params, $format, &$smarty) {
	global $PIVOTX;

	$params = cleanParams($params);
	
	// Store the template variables, so whatever happens in the formbuilder
	// can't screw up the rest of the page.
	$templatevars = $smarty->get_template_vars();
	$pageuri = getDefault($templatevars['page']['uri'], $templatevars['entry']['uri']);
	$pagelink = getDefault($templatevars['entry']['link'], $templatevars['page']['link']);

	//debug("Doet nu contactform voor: REQUEST_URI:". $_SERVER["REQUEST_URI"] ." / link:". $pagelink ." / uri:".$pageuri);

	if(isset($params['showinweblog'])) {
		$params['action'] = $pagelink;
	} else {
		$params['action'] = $_SERVER["REQUEST_URI"];
	}

	// If no internal id is set, force one
	if($params['id']!='') {
		$formid = safe_string($params['id'],true);
	} elseif(empty($PIVOTX['currentform'])) {
		$params['id'] = 'defaultform-'.md5($pagelink);
		$formid = $params['id'];
	}
	$PIVOTX['currentform'] = $formid;

	//$output .= '<h2>'. $PIVOTX['currentform'] .'</h2>';
	
	// build the global config array
	// This function gets called twice. Once when enter it, and once when
	// leaving the block. In the latter case we render the form and do stuff with it
	if (!isset($format)) {
		//print '<p>first call to form</p>';
		$PIVOTX['forms'][$PIVOTX['currentform']] = array();
		$PIVOTX['forms'][$PIVOTX['currentform']]['countfieldset'] = 0;
		$PIVOTX['forms'][$PIVOTX['currentform']]['countselect'] = 0;
		$PIVOTX['forms'][$PIVOTX['currentform']]['countradio'] = 0;
		return;
	}

	if($PIVOTX['currentform']) {
		$PIVOTX['forms'][$PIVOTX['currentform']]['config']['id'] = $formid;
		$PIVOTX['forms'][$PIVOTX['currentform']]['config']['name'] = $formid;
	}



	//$output = "<p>second call to form</p>";
	//$output .= '<div style="border:2px solid #f00; padding: 5px; overflow: scroll;">Input<br /><pre>'.$format."</pre></div>";
	//$output .= "<p>first check what params we have</p><pre>".print_r($params, true)."</pre>";
	//$output .= "<p>now parse form format</p>";


	//$output .= '<p>We have a form! it\'s: '.$PIVOTX['currentform'].'</p>';

	$cachekey = "tpl_".substr(md5($format),0,10);
	//$PIVOTX['template']->caching = false;
	$PIVOTX['template']->custom_template[$cachekey] = $format;
	$output .= $PIVOTX['template']->fetch("db:".$cachekey, $cachekey);

	// Re-enable caching, if desired..
	if($PIVOTX['config']->get('smarty_cache')){
		$PIVOTX['template']->caching = true;
	}

	$config = $PIVOTX['forms'][$PIVOTX['currentform']]['config'];

	/* SET DEFAULTS */
	$username = get_default($PIVOTX['db']->entry['user'] , $PIVOTX['pages']->currentpage['user']);
	$user = $PIVOTX['users']->getUser($username);
	// fallback recipient
	if(empty($config['mail_config']['recipient']['email'])
	&& !isemail($config['mail_config']['recipient']['email'])) {
		$config['mail_config']['recipient'] = $user['email'];
		$config['mail_config']['recipient'] = $user['nickname'];
		debug('fallback email recipient '. $user['email'] . " - " . $user['nickname']);
		$output .= '<div style="border:2px solid #f00; padding: 5px;">fallback email recipient '. $user['email'] . " - " . $user['nickname'].'</div>';
	}
	// fallback sender
	if(empty($config['mail_config']['sender']['email'])
	&& !isemail($config['mail_config']['sender']['email'])) {
		$config['mail_config']['sender'] = $user['email'];
		$config['mail_config']['sender'] = $user['nickname'];
		debug('fallback email sender '. $user['email'] . " - " . $user['nickname']);
		$output .= '<div style="border:2px solid #f00; padding: 5px;">fallback email sender '. $user['email'] . " - " . $user['nickname'].'</div>';
	}
	// default to mail if not set
	if(empty($config['mail_config']['method'])) {
		$config['mail_config']['method'] = 'mail';
	}
	// default blank template
	if(empty($config['templates']['mailreply'])) {
		$config['templates']['mailreply'] = '%posted_data%';
	}
	// default form elements
	if(empty($config['templates']['elements'])) {
		$config['templates']['elements'] = 'formclass_defaulthtml.php';
	}
	// default blank template
	if(empty($config['templates']['confirmation'])) {
		$config['templates']['confirmation'] = '%posted_data%';
	}
	// build something useful for the form id if not set
	if(empty($config['id'])) {
		$config['id'] = 'form-'.substr(md5($pagelink ."-". $user['nickname']),-10);
	}
	if(empty($config['name'])) {
		$config['name'] = $config['id'];
	}
	// default to post
	if(empty($config['action'])) {
		$config['action'] = '#';
	}
	// default method = post
	if(empty($config['method'])) {
		$config['method'] = (isset($params['method']) || $params['method']!='get')?'post':'get';
	}
	// no redirect
	if(empty($config['redirect'])) {
		$config['redirect'] = false;
	}
	// needs only to be set for forms with an upload action
	if(empty($config['encoding'])) {
		$config['encoding'] = '';
	}
	
	if(!isset($config['buttons']) || empty($config['buttons'])) {
		$config['buttons'] = array(
			'verzenden' => array(
				'type' => 'submit',
				'label' => '',
				'value' => 'Send message'
			)
		);
	}
	/* |END DEFAULTS */

	if(is_array($config)) {
		$form = new FormBuilder($config);
		$form->execute_form();
	} else {
		$output .= $config;
	}


	//$output .= '<div style="border:2px solid #f00; padding: 5px; margin-top: 10px; overflow: scroll;">Parser<br /><pre>'.var_export($PIVOTX['forms'], true)."</pre></div>";
	
	$output .= '<div style="border:2px solid #f00; padding: 5px; margin-top: 10px; overflow: scroll;">Config<br /><pre>'.var_export($config, true)."</pre></div>";

	// unset the current form so the next form will be something different
	// will break is the form has no ID
	unset($PIVOTX['currentform']);
	
	// Restore the saved template variables..
	$smarty->_tpl_vars = $templatevars;
	
	return $output;

}


// register block
$PIVOTX['template']->register_block('select', 'formbuilder_select');

function formbuilder_select($params, $format, &$smarty) {
	global $PIVOTX;
	$params = cleanParams($params);
	if(!$params['type']) {
		$params['type'] = 'select';
	}
	if(!$params['validation']) {
		$params['validation'] = 'options';
	}
	if(!$params['required'] || $params['required'] == 'false') {
		$params['validation'] = 'ifany|'.$params['validation'];
		
	}
	// This function gets called twice. Once when enter it, and once when
	// leaving the block. In the latter case we render the form and do stuff with it
	if (!isset($format)) {

		$PIVOTX['forms'][$PIVOTX['currentform']]['countselect']+=1;

		$params['name'] = $params['name']?$params['name']:false;
		if(!$params['id']) {
			$params['id'] = formbuilder_generateid($params['type'], safeString($params['name'],true));
		}
	
		//print "<p>fieldset start</p><fieldset><legend>". $params['id'] . '-' . $title."</legend>";

		$PIVOTX['forms'][$PIVOTX['currentform']]['currentselect'] = array_merge(array('name'=>'selectstart'), $params);

	 	//print '<p>select start</p><div class="select">';
		//$PIVOTX['forms'][$PIVOTX['currentform']][] = array_merge(array('name'=>'selectstart'), $params);
		return;
	}


	//printf ("<pre>%s</pre>", print_r($params, true));
	//$PIVOTX['forms'][$PIVOTX['currentform']][] = array_merge(array('name'=>'selectend'), $params);
	/**
	 * TODO: Doe hier een inline smarty eval achtig iets ipv een hele template parsen
	 */
	$cachekey = "tpl_".substr(md5($format),0,10);
	//$PIVOTX['template']->caching = false;
	$PIVOTX['template']->custom_template[$cachekey] = $format;
	$output .= $PIVOTX['template']->fetch("db:".$cachekey, $cachekey);

	$currentselect = $PIVOTX['forms'][$PIVOTX['currentform']]['currentselect'];
	//printf ("<pre>%s</pre>", print_r($currentselect, true));
	
	$fieldoptions = array('default', 'validation', 'value', 'required', 'pre_html', 'post_html', 'requiredmessage', 'error');
	
	if($currentselect['id']) {
		$curentselect['name'] = $currentselect['name'];
		$curentselect['label'] = $currentselect['name'];
		$curentselect['options'] = $currentselect['options'];
		$curentselect['type'] = $params['type'];
		foreach($fieldoptions as $key => $value) {
			if(isset($params[$value])) {
				$curentselect[$value] = $params[$value];
			} elseif (isset($curentselect[$value])){
				$curentselect[$value] = $curentselect[$value];
			}
		}
	}
	
	if($PIVOTX['forms'][$PIVOTX['currentform']]['currentfieldset']) {
		$PIVOTX['forms'][$PIVOTX['currentform']]['currentfieldset']['keys'][] = $currentselect['id'];
	}

	

	$PIVOTX['forms'][$PIVOTX['currentform']]['config']['fields'][$currentselect['id']] = $curentselect;
	
	unset($PIVOTX['forms'][$PIVOTX['currentform']]['currentselect']);
	return;

	//print "</div><p>select end</p>";
	return;
}

// register block
$PIVOTX['template']->register_block('radiogroup', 'formbuilder_radio');

function formbuilder_radio($params, $format, &$smarty) {
	global $PIVOTX;
	$params = cleanParams($params);
	if(!$params['type']) {
		$params['type'] = 'radio';
	}
	return formbuilder_select($params, $format, &$smarty);
}


// Register tag
$PIVOTX['template']->register_function('option', 'formbuilder_option');

function formbuilder_option($params, &$smarty) {
	global $PIVOTX;
	$params = cleanParams($params);
	$params['type'] = 'option';
	//printf("<p>option</p><pre>%s</pre>", print_r($params, true));
	if(!$params['title']) {
		$params['title'] = $params['value'];		
	}
	if($params['default']=='true') {
		$PIVOTX['forms'][$PIVOTX['currentform']]['currentselect']['default'] = $params['value'];
	}
	
	$PIVOTX['forms'][$PIVOTX['currentform']]['currentselect']['options'][safeString($params['value'])] = $params['title'];
	return;
}

// register block
$PIVOTX['template']->register_block('fieldset', 'formbuilder_fieldset');

function formbuilder_fieldset($params, $format, &$smarty) {
	global $PIVOTX;
	$params = cleanParams($params); 
	$params['type'] = 'fieldset';
	
	// This function gets called twice. Once when enter it, and once when
	// leaving the block. In the latter case we render the form and do stuff with it
	if (!isset($format)) {
	
		$PIVOTX['forms'][$PIVOTX['currentform']]['countfieldset']+=1;

		$params['title'] = $params['title']?$params['title']:false;
		if(!$params['id']) {
			$params['id'] = formbuilder_generateid($params['type'], safeString($params['title'],true));
		}
	
		//print "<p>fieldset start</p><fieldset><legend>". $params['id'] . '-' . $title."</legend>";

		$PIVOTX['forms'][$PIVOTX['currentform']]['currentfieldset'] = array_merge(array('name'=>'fieldsetstart'), $params);
		return;
	}

	//printf ("<textarea>%s</textarea>", print_r($format, true));
	//printf ("<pre>%s</pre>", print_r($params, true));
	//$PIVOTX['forms'][$PIVOTX['currentform']]['currentfieldset']['end'] = array_merge(array('name'=>'fieldsetend'), $params);
	
	/**
	 * TODO: Doe hier een inline smarty eval achtig iets ipv een hele template parsen
	 */
	$cachekey = "tpl_".substr(md5($format),0,10);
	//$PIVOTX['template']->caching = false;
	$PIVOTX['template']->custom_template[$cachekey] = $format;
	$output .= $PIVOTX['template']->fetch("db:".$cachekey, $cachekey);
	//$PIVOTX['forms'][$PIVOTX['currentform']][] = $format;
	//print $output;
	//print "</fieldset><p>fieldset end</p>";
	if($PIVOTX['forms'][$PIVOTX['currentform']]['currentfieldset']['id']) {
		$curentfieldsetid = $PIVOTX['forms'][$PIVOTX['currentform']]['currentfieldset']['id'];
		$curentfieldsettitle = $PIVOTX['forms'][$PIVOTX['currentform']]['currentfieldset']['title'];
		$curentfieldsetkeys = $PIVOTX['forms'][$PIVOTX['currentform']]['currentfieldset']['keys'];
	}
	$PIVOTX['forms'][$PIVOTX['currentform']]['config']['fieldsets'][$curentfieldsetid] = array(
		'label' => $curentfieldsettitle,
		'fields' => $curentfieldsetkeys
	);
	unset($PIVOTX['forms'][$PIVOTX['currentform']]['currentfieldset']);
	return;
}

// Register tag
$PIVOTX['template']->register_function('form_config', 'formbuilder_config');

function formbuilder_config($params, &$smarty) {
	global $PIVOTX;
	$params = cleanParams($params);	 
	$params['type'] = 'config';
	//debug(sprintf("<pre>%s</pre>", print_r($params, true)));
	//$PIVOTX['forms'][$PIVOTX['currentform']][] = $params;
	
	if(!$params['key']) {
		$keys = array_keys($params);
		//debug_printr($keys);
		if(in_array($keys[0], array(
			'subject',
			'action',
			'method',
			'mail_method',
			'encoding',
			'pre_html',
			'post_html',
			'to',
			'from',
			'cc',
			'bcc',
			'redirect',
			'mailtemplate',
			'displaytemplate'
		))) {
			$params['key'] = $keys[0];
			$params['value'] = $params[$keys[0]];
		}
	}
	//debug_printr($params);
	
	switch($params['key']) {
		case('to'):
		case('from'):
		case('cc'):
		case('bcc'):
			if($params['key']=='to') {
				$fieldlabel = 'recipient';
			} elseif($params['key']=='from') {
				$fieldlabel = 'sender';
			} else {
				$fieldlabel = $params['key'];
			}
			if(!($params['formfield_name'] && $params['formfield_email'])) {
				$PIVOTX['forms'][$PIVOTX['currentform']]['config']['mail_config'][$fieldlabel]['email'] = $params['email'];
				$PIVOTX['forms'][$PIVOTX['currentform']]['config']['mail_config'][$fieldlabel]['name'] = $params['name'];
			} else {
				$PIVOTX['forms'][$PIVOTX['currentform']]['config']['mail_config'][$fieldlabel]['name'] = false;
				$PIVOTX['forms'][$PIVOTX['currentform']]['config']['mail_config'][$fieldlabel]['email'] = false;
				$PIVOTX['forms'][$PIVOTX['currentform']]['config']['mail_config'][$fieldlabel]['formfield_name'] = $params['formfield_name'];
				$PIVOTX['forms'][$PIVOTX['currentform']]['config']['mail_config'][$fieldlabel]['formfield_email'] = $params['formfield_email'];
			}
			break;
		case 'mail_method':
			$PIVOTX['forms'][$PIVOTX['currentform']]['config']['mail_config']['method'] = ($params['value']=="smtp")?"smtp":"mail";
			break;
		case 'id';
			$PIVOTX['forms'][$PIVOTX['currentform']]['config']['id'] = safe_string($params['value'],true);
			$PIVOTX['forms'][$PIVOTX['currentform']]['config']['name'] = safe_string($params['value'],true);
			break;
		case 'subject':
			$PIVOTX['forms'][$PIVOTX['currentform']]['config']['mail_config'][$params['key']] = $params['value'];
			break;
		case 'redirect':
			$PIVOTX['forms'][$PIVOTX['currentform']]['config']['redirect'] = $params['value'];
			break;
		case 'mailtemplate':
			$PIVOTX['forms'][$PIVOTX['currentform']]['config']['templates']['mailreply'] = $params['value'];
			break;
		case 'elements':
			$PIVOTX['forms'][$PIVOTX['currentform']]['config']['templates']['elements'] = $params['value'];
			break;
		case 'displaytemplate':
			$PIVOTX['forms'][$PIVOTX['currentform']]['config']['templates']['confirmation'] = $params['value'];
			break;
		default:
			$PIVOTX['forms'][$PIVOTX['currentform']]['config'][$params['key']] = $params['value'];
			break;
	}
	return;
}


// Register tag
$PIVOTX['template']->register_function('textfield', 'formbuilder_text');

function formbuilder_text($params, &$smarty) {
	global $PIVOTX;
	$params = cleanParams($params);
	
	if(!isset($params['type'])) {
		$params['type'] = 'text';
	}
	//printf("<pre>%s</pre>", print_r($params, true));
	if(!$params['label']) {
		$params['label'] = $params['name'];
	}
	$params['name'] = str_replace(" ", '', safeString($params['name']));

	if(isset($params['required']) && $params['required']!='false') {
		$params['required'] = 'true';
		if(empty($params['validation'])) {
			$params['validation'] = 'string';
		}
	} elseif(!isset($params['required']) || $params['required']=='false') {
		if(!empty($params['validation'])) {
			$params['validation'] = 'ifany|'.$params['validation'];
		}
	}

	if(!$params['id']) {
		$params['id'] = formbuilder_generateid('text', $params['name']);
	}
	if($PIVOTX['forms'][$PIVOTX['currentform']]['currentfieldset']) {
		$PIVOTX['forms'][$PIVOTX['currentform']]['currentfieldset']['keys'][] = $params['id'];
	}
	
	$PIVOTX['forms'][$PIVOTX['currentform']]['config']['fields'][$params['id']] = $params;
	//$PIVOTX['forms'][$PIVOTX['currentform']][] = $params;
	return;
}

// Register tag
$PIVOTX['template']->register_function('emailfield', 'formbuilder_email');

function formbuilder_email($params, &$smarty) {
	global $PIVOTX;
	$params = cleanParams($params); 
	$params['type'] = 'email';
	if(!$params['label']) {
		$params['label'] = $params['name'];
	}
	
	$params['type'] = 'text';
	$params['validation'] = 'email';

	if(!$params['id']) {
		$params['id'] = formbuilder_generateid('email', $params['name']);
	}

	return formbuilder_text($params, &$smarty);
}	

// Register tag
$PIVOTX['template']->register_function('textarea', 'formbuilder_textarea');

function formbuilder_textarea($params, &$smarty) {
	global $PIVOTX;
	$params = cleanParams($params);
	$params['type'] = 'textarea';
	if(!$params['label']) {
		$params['label'] = $params['name'];
	}
	if(!$params['id']) {
		$params['id'] = formbuilder_generateid('textarea', $params['name']);
	}
	if($PIVOTX['forms'][$PIVOTX['currentform']]['currentfieldset']) {
		$PIVOTX['forms'][$PIVOTX['currentform']]['currentfieldset']['keys'][] = $params['id'];
	}

	$PIVOTX['forms'][$PIVOTX['currentform']]['config']['fields'][$params['id']] = $params;
	//printf("<pre>%s</pre>", print_r($params, true));
	//$PIVOTX['forms'][$PIVOTX['currentform']][] = $params;
	return;
}

// Register tag
$PIVOTX['template']->register_function('formbutton', 'formbuilder_button');

function formbuilder_button($params, &$smarty) {
	global $PIVOTX;
	$params = cleanParams($params);
	if(!isset($params['action'])) {
		$params['type'] = 'button';
	} else {
		$params['type'] = $params['action'];
	}
	if(!$params['id']) {
		$params['id'] = formbuilder_generateid('button');
	}
	if($PIVOTX['forms'][$PIVOTX['currentform']]['currentfieldset']) {
		$PIVOTX['forms'][$PIVOTX['currentform']]['currentfieldset']['keys'][] = $params['id'];
	}
	//printf("<pre>%s</pre>", print_r($params, true));
	$PIVOTX['forms'][$PIVOTX['currentform']]['config']['buttons'][$params['id']] = $params;
	return;
}

// Register tag
$PIVOTX['template']->register_function('checkbox', 'formbuilder_checkbox');

function formbuilder_checkbox($params, &$smarty) {
	global $PIVOTX;
	$params = cleanParams($params); 
	$params['type'] = 'checkbox';
	if(!$params['label']) {
		$params['label'] = $params['name'];
	}
	if(!$params['id']) {
		$params['id'] = formbuilder_generateid('checkbox', $params['name']);
	}
	if($PIVOTX['forms'][$PIVOTX['currentform']]['currentfieldset']) {
		$PIVOTX['forms'][$PIVOTX['currentform']]['currentfieldset']['keys'][] = $params['id'];
	}

	$PIVOTX['forms'][$PIVOTX['currentform']]['config']['fields'][$params['id']] = $params;
	//printf("<pre>%s</pre>", print_r($params, true));
	//$PIVOTX['forms'][$PIVOTX['currentform']][] = $params;
	return;
}

// Register tag
$PIVOTX['template']->register_function('markup', 'formbuilder_markup');

function formbuilder_markup($params, &$smarty) {
	global $PIVOTX;
	$params = cleanParams($params);
	$params['type'] = 'custom';

	$params['name'] = getDefault($params['name'], substr(md5(session_id() ."-". mt_rand()),-10) );
	if(!$params['id']) {
		$params['id'] = formbuilder_generateid('markup', $params['name']);
	}
	if($params['content'] && empty($params['text'])) {
		$params['text'] = $params['content'];
	}
	
	if($PIVOTX['forms'][$PIVOTX['currentform']]['currentfieldset']) {
		$PIVOTX['forms'][$PIVOTX['currentform']]['currentfieldset']['keys'][] = $params['id'];
	}

	$PIVOTX['forms'][$PIVOTX['currentform']]['config']['fields'][$params['id']] = $params;
	//$PIVOTX['forms'][$PIVOTX['currentform']][] = $params;
	return;
}

function formbuilder_generateid($type='markup', $name='') {
	$name = ($name)?$name:$type;
	$name = 'id-'.$name .'-'. substr(md5(session_id().mt_rand()),-10);
	$id = safe_string($name,true);
	return $id;
}