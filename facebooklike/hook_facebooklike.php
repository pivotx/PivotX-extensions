<?php
// - Extension: Facebook Like Button
// - Version: 1.0
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: An extension to place a Facebook 'Like' button on your entries and pages.
// - Date: 2010-08-13
// - Identifier: facebooklikebutton

// Register 'facebook_like' as a smarty tag.
$PIVOTX['template']->register_function('facebook_like', 'smarty_facebook_like');

function smarty_facebook_like($params, &$smarty) {
    global $PIVOTX;

    $vars = $smarty->get_template_vars();
    $entry = $vars['entry'];
    $page = $vars['page'];

	$host = stripTrailingSlash($PIVOTX['config']->get('canonical_host'));

	// Setting variables for the facebook widget..
	$width = getDefault($params['width'], '450');
	$height = getDefault($params['height'], '80');
	$urlparams['layout'] = "layout=" . getDefault($params['layout'], 'standard');
	$urlparams['show_faces'] = "show_faces=" . getDefault($params['show_faces'], 'true');
	$urlparams['action'] = "action=" . getDefault($params['action'], 'like');
	$urlparams['font'] = "font=" . getDefault($params['font'], 'arial');
	$urlparams['colorscheme'] = "colorscheme=" . getDefault($params['colorscheme'], 'light');

	if (!empty($params['link'])) {
		$urlparams['href'] = "href=" . urlencode($host.$params['link']);
	} else if (!empty($entry['link'])) {
		$urlparams['href'] = "href=" . urlencode($host.$entry['link']);		
	} else {
		$urlparams['href'] = "href=" . urlencode($host.$page['link']);
	}
	
	$urlparams = implode("&amp;", $urlparams);
	
	$html = "<iframe src=\"http://www.facebook.com/widgets/like.php?{$urlparams}\"
        scrolling=\"no\" frameborder=\"0\"
        style=\"border:none; width:{$width}px; height:{$height}px\"></iframe>";
		
	return $html;

}
    
?>
