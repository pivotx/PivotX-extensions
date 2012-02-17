<?php
// - Extension: Facebook Like Button
// - Version: 1.12
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: An extension to place a Facebook 'Like' button on your entries and pages.
// - Date: 2012-02-17
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
    if (isset($params['locale'])) {
        $urlparams['locale'] = "locale=" . $params['locale'];
    }
    if (isset($params['ref'])) {
        $urlparams['ref'] = "ref=" . str_replace(' ','*',substr($params['ref'],0,50));
    }

    if ((!isset($params['link'])) && (isset($params['href']))) {
        $params['link'] = $params['href'];
    }

    if (isset($params['canonical']) && ($params['canonical'] == true) && (method_exists($PIVOTX['parser'],'getCanonicalUrl'))) {
        $params['link'] = $PIVOTX['parser']->getCanonicalUrl();
    }
    if ((isset($params['uri'])) && ($params['uri'] != '')) {
        $params['link'] = smarty_link(array('uri'=>$params['uri'],'hrefonly'=>1),$PIVOTX['template']);
    }

    if (isset($params['link']) && !empty($params['link'])) {
        if (preg_match('|^https?://|',$params['link'],$match)) {
            $urlparams['href'] = "href=" . urlencode($params['link']);
        } else {
            $urlparams['href'] = "href=" . urlencode($host.$params['link']);
        }
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
