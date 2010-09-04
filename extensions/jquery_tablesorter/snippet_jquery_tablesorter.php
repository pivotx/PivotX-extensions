<?php
// - Extension: jQuery Tablesorter
// - Version: 1.0
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: An extension that adds sorting to tables 
// - Date: 2010-05-01
// - Identifier: jquery_tablesorter
// - Required PivotX version: 2.0.2

// Make sure we have jQuery..
$this->addHook('after_parse', 'callback', 'jqueryIncludeCallback');

$PIVOTX['template']->register_function('jquery_tablesorter', 'smarty_jquery_tablesorter');

/**
 * Adds the need Javascript code and CSS for table sorting.
 *
 * @param array $params
 * @param object $smarty
 * @return unknown
 */
function smarty_jquery_tablesorter($params) {
    global $PIVOTX;

    static $initialized = false;
    static $tablecount;

    $path = $PIVOTX['paths']['extensions_url'] . "jquery_tablesorter/";

    if (isset($params['css_file'])) {
        $css_file = $PIVOTX['paths']['extensions_url'] . $params['css_file'];
    } else {
        $css_file = "${path}blue_theme/style.css";
    }

    if (!$initialized) {
        $initialized = true;
        $js_head = <<<EOM
    <link href='$css_file' rel='stylesheet' type='text/css' media='screen' />
    <script type='text/javascript' src='${path}jquery.tablesorter.min.js'></script>
EOM;
        $PIVOTX['extensions']->addHook('after_parse', 'insert_before_close_head', $js_head);
        $PIVOTX['extensions']->addHook('after_parse', 'callback', 'jqueryIncludeCallback');
    }

    $id = getDefault($params['id'], "tablesorter$tablecount");
    $class = getDefault($params['class'], 'tablesorter');
    $options = getDefault($params['options'], 'sortList: [ [0,0] ]');

    $js_insert .= <<<EOM
    <script type='text/javascript'>
        jQuery(document).ready(function() 
            {
                jQuery("#$id").tablesorter({
                    $options
                });
            }
        );
    </script>
EOM;
/*
                    sortList: [[0,0]],
                    headers: { 3: {sorter: false} }
  */  
    $PIVOTX['extensions']->addHook('after_parse', 'insert_before_close_head', $js_insert);

    $tablecount++;

    if ($params['only_head']) {
        return;
    } else {
        return " id='$id' class='$class' ";
    }

}

?>
