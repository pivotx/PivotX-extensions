<?php
// - Extension: Formbuilder and contactforms
// - Version: 0.19
// - Author: Two Kings // Lodewijk Evers
// - E-mail: lodewijk@twokings.nl
// - Description: Add form templates and [[contactform]] snippets to your entries and pages
// - Date: 2010-05-03
// - Identifier: formbuilder
// - Required PivotX version: 2.1

if(!class_exists('FormBuilder')) {
	$formbuilderbasedir = dirname(__FILE__);
	if(file_exists($formbuilderbasedir.'/form.class.php')) {
		include_once($formbuilderbasedir.'/form.class.php');
	}
	include_once($formbuilderbasedir.'/_formbuilder_contactform.php');
	include_once($formbuilderbasedir.'/_formbuilder_orderform.php');
	include_once($formbuilderbasedir.'/_formbuilder_tags.php');
}

// disable krumo debugging
// remove the "0 &&" part to enable krumo debugging
if(0 && file_exists($formbuilderbasedir.'/krumo/class.krumo.php')) {
  include_once($formbuilderbasedir.'/krumo/class.krumo.php');
}
