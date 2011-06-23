<?php
// - Extension: Formbuilder and contactforms
// - Version: 0.26
// - Author: Two Kings // Lodewijk Evers
// - E-mail: lodewijk@twokings.nl
// - Description: Add form templates and [[contactform]] snippets to your entries and pages
// - Date: 2011-04-27
// - Identifier: formbuilder
// - Required PivotX version: 2.2.0


$PIVOTX['formbuilder']['lang'] = get_default($PIVOTX['config']->get('formbuilder_language'), 'default');

if(!class_exists('FormBuilder')) {
	$formbuilderbasedir = dirname(__FILE__);
	// load the basic formbuilder ([[PHP]] version)
	if(file_exists($formbuilderbasedir.'/form.class.php')) {
		include_once($formbuilderbasedir.'/form.class.php');
	}

	if($PIVOTX['config']->get('db_model')=='mysql') {
		// only load the sql for the extension if it is needed
		if(file_exists($formbuilderbasedir.'/form.sql.php')) {
			include_once($formbuilderbasedir.'/form.sql.php');
		}
	}
	
	if($PIVOTX['formbuilder']['lang']!='default') {
		if(file_exists($formbuilderbasedir.'/translations/'.$PIVOTX['formbuilder']['lang'].'.php')) {
			include_once($formbuilderbasedir.'/translations/'.$PIVOTX['formbuilder']['lang'].'.php');
			//debug_printr($translations);
			$PIVOTX['formbuilder'][$PIVOTX['formbuilder']['lang']] = $translations;
		}
		if(file_exists($formbuilderbasedir.'/overrides/translations/'.$PIVOTX['formbuilder']['lang'].'.php')) {
			include_once($formbuilderbasedir.'/overrides/translations/'.$PIVOTX['formbuilder']['lang'].'.php');
			$PIVOTX['formbuilder'][$PIVOTX['formbuilder']['lang']] = array_merge(
				$PIVOTX['formbuilder'][$PIVOTX['formbuilder']['lang']],
				$translations
			);
		}
	}
	
	$dir = scandir($formbuilderbasedir);
	//debug_printr($dir);
	foreach($dir as $filename) {
		if(strstr($filename, '_formbuilder_') && !strstr($filename, '.__')) {
			include_once($formbuilderbasedir.'/'.$filename);
		}
		
	}

}

/**
 * This ugly function will translate your (error)messages
 */
function ft($unstranslatedstring) {
	global $PIVOTX;
	
	// If a language is set, see if the string is translated
	if(
	   $PIVOTX['formbuilder']['lang']
	   && is_array($PIVOTX['formbuilder'][$PIVOTX['formbuilder']['lang']])
	   && isset($PIVOTX['formbuilder'][$PIVOTX['formbuilder']['lang']][$unstranslatedstring])
	) {
		// return the translated string
		return $PIVOTX['formbuilder'][$PIVOTX['formbuilder']['lang']][$unstranslatedstring];
	}
	
	// fallback to default pivotx translation
	$translatedstring = __($unstranslatedstring);
	if($translatedstring!=$unstranslatedstring) {
		return $translatedstring;
	}
	// big failure, return the untranslated string
	return $unstranslatedstring;
}