<?php
// - Extension: Formbuilder and contactforms
// - Version: 0.26
// - Author: Two Kings // Lodewijk Evers
// - E-mail: lodewijk@twokings.nl
// - Description: Add form templates and [[contactform]] snippets to your entries and pages
// - Date: 2011-01-07
// - Identifier: formbuilder
// - Required PivotX version: 2.2.0


$PIVOTX['formbuilder']['lang'] = $PIVOTX['config']->get('formbuilder_language');

if(!class_exists('FormBuilder')) {
	$formbuilderbasedir = dirname(__FILE__);
	// load the basic formbuilder ([[PHP]] version)
	if(file_exists($formbuilderbasedir.'/form.class.php')) {
		include_once($formbuilderbasedir.'/form.class.php');
	}

	if($PIVOTX['config']->get('db_model')=='mysql') {
		// only load the sql for the extension if it is needed
		if(file_exists($formbuilderbasedir.'/form.sql.php')) {
			include_once($formbuilderbasedir.'/form.admin.php');
			include_once($formbuilderbasedir.'/form.sql.php');
		}
	}
	
	if(file_exists($formbuilderbasedir.'/translations/'.$PIVOTX['formbuilder']['lang'].'.php')) {
		include_once($formbuilderbasedir.'/translations/'.$PIVOTX['formbuilder']['lang'].'.php');
		$PIVOTX['formbuilder'][$PIVOTX['formbuilder']['lang']] = $translations;
	}
	if(file_exists($formbuilderbasedir.'/overrides/translations/'.$PIVOTX['formbuilder']['lang'].'php')) {
		include_once($formbuilderbasedir.'/overrides/translations/'.$PIVOTX['formbuilder']['lang'].'.php');
		$PIVOTX['formbuilder'][$PIVOTX['formbuilder']['lang']] = $translations;
	}


	// add the [[contactform]]
	include_once($formbuilderbasedir.'/_formbuilder_contactform.php');
	// add the [[orderform]]
	include_once($formbuilderbasedir.'/_formbuilder_orderform.php');
	// add the [[sendtofriend]]
	include_once($formbuilderbasedir.'/_formbuilder_sendtofriend.php');
	// add the [[formbuilder]]
	include_once($formbuilderbasedir.'/_formbuilder_tags.php');
}

/**
 * This ugly function will translate your (error)messages
 */
function ft($unstranslatedstring) {
	global $PIVOTX;
	
	$translatedstring = __($unstranslatedstring);
	
	if(isset($PIVOTX['formbuilder'][$PIVOTX['formbuilder']['lang']][$unstranslatedstring])) {
		return $PIVOTX['formbuilder'][$PIVOTX['formbuilder']['lang']][$unstranslatedstring];
	} elseif($translatedstring!=$unstranslatedstring) {
		return $translatedstring;
	}
	return $unstranslatedstring;
}