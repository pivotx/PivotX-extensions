<?php
// - Extension: Twitter Button
// - Version: 1.0.3
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: An extension to place a Twitter button on your entries and pages.
// - Date: 2012-02-22
// - Identifier: twitterbutton

// Register 'twitterbutton' as a smarty tag.
$PIVOTX['template']->register_function('twitterbutton', 'smarty_twitterbutton');

function smarty_twitterbutton($params, &$smarty) {
    global $PIVOTX;

    $vars = $smarty->get_template_vars();
    $entry = $vars['entry'];
    $page = $vars['page'];

    $host = stripTrailingSlash($PIVOTX['config']->get('canonical_host'));

    // Setting variables for the Twitter button..
    $label = "Tweet";
    $button = getDefault($params['button'], 'horizontal');
    $username = getDefault($params['username'], '');
    $size = getDefault($params['size'], 'medium');

    if (!empty($params['link'])) {
        $link = addslashes($params['link']);
    } else if (!empty($entry['link'])) {
        $link = addslashes($host.$entry['link']);        
    } else {
        $link = addslashes($host.$page['link']);
    }
    
    if (!empty($params['text'])) {
        $text = addslashes($params['text']);
    } else if (!empty($entry['title'])) {
        $text = addslashes($entry['title']);    
    } else {
        $text = addslashes($page['title']);
    }
        
    $html = "<a href=\"http://twitter.com/share\" class=\"twitter-share-button\" data-size=\"{$size}\" " .
        "data-url=\"{$link}\" data-text=\"{$text}\" data-count=\"{$button}\" data-via=\"{$username}\">" .
        "{$label}</a><script type=\"text/javascript\" src=\"http://platform.twitter.com/widgets.js\"></script>";
        
    return $html;

}
    
?>
