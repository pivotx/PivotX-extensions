<?php

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

    // This function gets called twice. Once when enter it, and once when
    // leaving the block. In the latter case we render the form and do stuff with it
    if (!isset($format)) {// print '<p>first call to form</p>';
	return; }

    // Store the template variables, so whatever happens in the formbuilder
    // can't screw up the rest of the page.
    $templatevars = $smarty->get_template_vars();

	//$output = "<p>second call to form</p>";
	//$output .= '<textarea cols="60" rows="8">'.$format."</textarea>";
	//$output .= "<p>first check what params we have</p><pre>".print_r($params, true)."</pre>";
    //$output .= "<p>now parse form format</p>";
	if($params['id']!='') {
		$formid = safe_string($params['id'],true);
		$PIVOTX['currentform'] = $formid;
		$PIVOTX['forms'][$PIVOTX['currentform']] = array();
	}
	
	$cachekey = "tpl_".substr(md5($format),0,10);
	//$PIVOTX['template']->caching = false;
	$PIVOTX['template']->custom_template[$cachekey] = $format;
	$output .= $PIVOTX['template']->fetch("db:".$cachekey, $cachekey);
	
    // Re-enable caching, if desired..
    if($PIVOTX['config']->get('smarty_cache')){
        $PIVOTX['template']->caching = true;
    }

	if(is_array($config)) {
		$form = new FormBuilder($config);
		$form->execute_form();
	} else {
		$output .= $config;
	}

	$output .= '<textarea cols="60" rows="8">'.var_export($PIVOTX['forms'][$PIVOTX['currentform']], true)."</textarea>";

	
    // Restore the saved template variables..
    $smarty->_tpl_vars = $templatevars;

    return $output;

}


// register block
$PIVOTX['template']->register_block('select', 'formbuilder_select');

function formbuilder_select($params, $format, &$smarty) {
	global $PIVOTX;
    $params = cleanParams($params);
    $params['type'] = 'select';
    // This function gets called twice. Once when enter it, and once when
    // leaving the block. In the latter case we render the form and do stuff with it
    if (!isset($format)) { //print '<p>select start</p><div class="select">';
	return; }
	
    //printf ("<pre>%s</pre>", print_r($params, true));
	$PIVOTX['forms'][$PIVOTX['currentform']][] = $params;
	/**
	 * TODO: Doe hier een inline smarty eval achtig iets ipv een hele template parsen
	 */
	$cachekey = "tpl_".substr(md5($format),0,10);
	//$PIVOTX['template']->caching = false;
	$PIVOTX['template']->custom_template[$cachekey] = $format;
	$output .= $PIVOTX['template']->fetch("db:".$cachekey, $cachekey);
	//$PIVOTX['forms'][$PIVOTX['currentform']][] = $format;
    //print $output;
    //print "</div><p>select end</p>";
    return;
}

// register block
$PIVOTX['template']->register_block('radiogroup', 'formbuilder_radio');

function formbuilder_radio($params, $format, &$smarty) {
	global $PIVOTX;
    $params = cleanParams($params);
    $params['type'] = 'radio';
    // This function gets called twice. Once when enter it, and once when
    // leaving the block. In the latter case we render the form and do stuff with it
    if (!isset($format)) { //print '<p>radio start</p><div class="radio">';
	return; }
	
	//printf ("<pre>%s</pre>", print_r($params, true));
	$PIVOTX['forms'][$PIVOTX['currentform']][] = $params;
	/**
	 * TODO: Doe hier een inline smarty eval achtig iets ipv een hele template parsen
	 */
	$cachekey = "tpl_".substr(md5($format),0,10);
	//$PIVOTX['template']->caching = false;
	$PIVOTX['template']->custom_template[$cachekey] = $format;
	$output .= $PIVOTX['template']->fetch("db:".$cachekey, $cachekey);
    //$PIVOTX['forms'][$PIVOTX['currentform']][] = $format;
    //print $output;
    //print "</div><p>radio end</p>";
    return;
}


// Register tag
$PIVOTX['template']->register_function('option', 'formbuilder_option');

function formbuilder_option($params, &$smarty) {
	global $PIVOTX;
    $params = cleanParams($params);
    $params['type'] = 'option';
    //printf("<p>option</p><pre>%s</pre>", print_r($params, true));
    $PIVOTX['forms'][$PIVOTX['currentform']][] = $params;
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
       // $title = $params['title']?$params['title']:"generated fieldset without title";
        //print "<p>fieldset start</p><fieldset><legend>".$title."</legend>";
        return;
    }

	//printf ("<textarea>%s</textarea>", print_r($format, true));
	//printf ("<pre>%s</pre>", print_r($params, true));
	$PIVOTX['forms'][$PIVOTX['currentform']][] = $params;
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
	return;
}

// Register tag
$PIVOTX['template']->register_function('form_config', 'formbuilder_config');

function formbuilder_config($params, &$smarty) {
	global $PIVOTX;
    $params = cleanParams($params);     
    $params['type'] = 'config';
    //printf("<pre>%s</pre>", print_r($params, true));
    $PIVOTX['forms'][$PIVOTX['currentform']][] = $params;
    return;
}


// Register tag
$PIVOTX['template']->register_function('textfield', 'formbuilder_text');

function formbuilder_text($params, &$smarty) {
	global $PIVOTX;
    $params = cleanParams($params);  
    $params['type'] = 'text';
    //printf("<pre>%s</pre>", print_r($params, true));
    $PIVOTX['forms'][$PIVOTX['currentform']][] = $params;
    return;
}

// Register tag
$PIVOTX['template']->register_function('emailfield', 'formbuilder_email');

function formbuilder_email($params, &$smarty) {
	global $PIVOTX;
    $params = cleanParams($params); 
    $params['type'] = 'email';
    //printf("<pre>%s</pre>", print_r($params, true));
    $PIVOTX['forms'][$PIVOTX['currentform']][] = $params;
    return;
}    

// Register tag
$PIVOTX['template']->register_function('textarea', 'formbuilder_textarea');

function formbuilder_textarea($params, &$smarty) {
	global $PIVOTX;
    $params = cleanParams($params);
    $params['type'] = 'textarea';
    //printf("<pre>%s</pre>", print_r($params, true));
    $PIVOTX['forms'][$PIVOTX['currentform']][] = $params;
    return;
}

// Register tag
$PIVOTX['template']->register_function('formbutton', 'formbuilder_button');

function formbuilder_button($params, &$smarty) {
	global $PIVOTX;
    $params = cleanParams($params);   
    $params['type'] = 'button';
    //printf("<pre>%s</pre>", print_r($params, true));
    $PIVOTX['forms'][$PIVOTX['currentform']][] = $params;
    return;
}

// Register tag
$PIVOTX['template']->register_function('checkbox', 'formbuilder_checkbox');

function formbuilder_checkbox($params, &$smarty) {
	global $PIVOTX;
    $params = cleanParams($params); 
    $params['type'] = 'checkbox';
    //printf("<pre>%s</pre>", print_r($params, true));
    $PIVOTX['forms'][$PIVOTX['currentform']][] = $params;
    return;
}

// Register tag
$PIVOTX['template']->register_function('markup', 'formbuilder_markup');

function formbuilder_markup($params, &$smarty) {
	global $PIVOTX;
    $params = cleanParams($params);
    $params['type'] = 'markup';
    //printf("<pre>%s</pre>", print_r($params, true));
    $PIVOTX['forms'][$PIVOTX['currentform']][] = $params;
    return;
}