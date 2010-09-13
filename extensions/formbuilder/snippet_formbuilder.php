<?php
// - Extension: Formbuilder and contactforms
// - Version: 0.22
// - Author: Two Kings // Lodewijk Evers
// - E-mail: lodewijk@twokings.nl
// - Description: Add form templates and [[contactform]] snippets to your entries and pages
// - Date: 2010-08-03
// - Identifier: formbuilder
// - Required PivotX version: 2.1.0 beta7

if(!class_exists('FormBuilder')) {
	$formbuilderbasedir = dirname(__FILE__);
	// load the basic formbuilder ([[PHP]] version)
	if(file_exists($formbuilderbasedir.'/form.class.php')) {
		include_once($formbuilderbasedir.'/form.class.php');
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
