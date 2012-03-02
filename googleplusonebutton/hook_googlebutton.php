<?php
// - Extension: Google 'Plus 1' Button
// - Version: 1.0.3
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: An extension to place a Google 'Plus 1' button on your entries and pages.
// - Date: 2012-03-02
// - Identifier: plusonebutton

// Register 'plusonebutton' as a smarty tag.
$PIVOTX['template']->register_function('plusonebutton', 'smarty_plusonebutton');

function smarty_plusonebutton($params, &$smarty) {
    global $PIVOTX;

    $vars = $smarty->get_template_vars();
    $entry = $vars['entry'];
    $page = $vars['page'];

    $host = stripTrailingSlash($PIVOTX['config']->get('canonical_host'));

    // Setting variables for the plus one button..
    // not used -- $button = getDefault($params['button'], 'horizontal');
    // not used -- $username = getDefault($params['username'], '');

    if (!empty($params['count'])) {
        $showcount = "true";
    } else {
        $showcount = "false";    
    }

    if (empty($params['size'])) {
        $size = "tall";
    } else {
        $size = safe_string($params['size']);    
    }    

    if (empty($params['lang'])) {
        $lang = $PIVOTX['config']->get('language');
    } else {
        $lang = safe_string($params['lang']);    
    }    

    if (!empty($params['link'])) {
        $link = addslashes($params['link']);
    } else if (!empty($entry['link'])) {
        $link = addslashes($host.$entry['link']);       
    } else {
        $link = addslashes($host.$page['link']);
    }

    $html = "<script type=\"text/javascript\" src=\"https://apis.google.com/js/plusone.js\">
            {\"lang\": \"{$lang}\"}
            </script>" .
        "<div class=\"g-plusone\" data-link=\"{$link}\" data-count=\"{$showcount}\" data-size=\"{$size}\"></div>";
        
    return $html;
}
?>