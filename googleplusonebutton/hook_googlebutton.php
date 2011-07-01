<?php
// - Extension: Google 'Plus 1' Button
// - Version: 1.0.1
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: An extension to place a Google 'Plus 1' button on your entries and pages.
// - Date: 2011-07-01
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
	$button = getDefault($params['button'], 'horizontal');
	$username = getDefault($params['username'], '');

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

	if (!empty($params['link'])) {
		$link = addslashes($params['link']);
	} else if (!empty($entry['link'])) {
		$link = addslashes($host.$entry['link']);		
	} else {
		$link = addslashes($host.$page['link']);
	}
	
		
	// <script type="text/javascript" src="https://apis.google.com/js/plusone.js"></script>
	// <div class="g-plusone" data-size="standard" data-count="true"></div>	
		
	$html = "<a href=\"http://twitter.com/share\" class=\"twitter-share-button\" " .
		"data-url=\"{$link}\" data-text=\"{$text}\" data-count=\"{$button}\" data-via=\"{$username}\">" .
		"{$label}</a><script type=\"text/javascript\" src=\"http://platform.twitter.com/widgets.js\"></script>";
	
	$html = "<script type=\"text/javascript\" src=\"https://apis.google.com/js/plusone.js\"></script>" .
	    "<div class=\"g-plusone\" data-link=\"{$link}\" data-count=\"{$showcount}\" data-size=\"{$size}\"></div>";
		
		
	return $html;

}
    
?>
