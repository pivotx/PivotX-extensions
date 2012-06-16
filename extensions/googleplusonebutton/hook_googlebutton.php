<?php
// - Extension: Google 'Plus 1' Button
// - Version: 1.2
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: An extension to place a Google 'Plus 1' button on your entries and pages.
// - Date: 2012-06-16
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

    // count is deprecated and replaced by annotation by google; left intact for downward compatibility
    // main reason being that default display was without count except for size tall
    if (!empty($params['count'])) {
        $showcount = "true";
        if ($params['count'] == 0) { $showcount = "false"; }
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

    if (empty($params['annotation'])) {
        $annotation = "none";
        // to keep things downwards compatible
        if ($size == "tall" || $showcount == "true") { $annotation = "ballon"; }
    } else {
        $annotation = safe_string($params['annotation']);  
        if ($annotation != "none" && empty($params['count'])) {
            $showcount = "true";
            if ($params['count'] == 0) { $showcount = "false"; }
        } 
    }
    // specifies the place the bubble is shown when mouse-over
    if (empty($params['bubble'])) {
        $bubble = "bottom";
    } else {
        $bubble = safe_string($params['bubble']);    
    }
    // default is 450; when using ballon it's set to 120 and when using none not set
    // this is all for downward compatibility because Google started to use the width specified
    // if specified the minimum is 120.
    if (empty($params['width'])) {
        $width = "450";
        if ($annotation == "ballon") {
            $width = "120";
            if ($size == "tall") { $width = "0"; }
        }
        if ($annotation == "none") { $width = "0"; }
    } else {
        $width = safe_string($params['width']);    
    }
    // alignment within width specified (now for all annotations -- defaults to left)
    if (empty($params['align'])) {
        $align = "left";
    } else {
        $align = safe_string($params['align']);    
    }

    if (!empty($params['link'])) {
        $link = addslashes($params['link']);
    } else if (!empty($entry['link'])) {
        $link = addslashes($host.$entry['link']);       
    } else {
        $link = addslashes($host.$page['link']);
    }
    // add the script to the header (so it will only be added once)
    $g1script = "{\"lang\": \"{$lang}\"}";
    $g1jsloc  = "https://apis.google.com/js/plusone.js";
    OutputSystem::instance()->addCode(
        'googleplusone-js',
        OutputSystem::LOC_HEADEND,
        'script',
        array('src'=>$g1jsloc,'_priority'=>OutputSystem::PRI_NORMAL+41),
        $g1script
    );
    // deprecated count removed (see remark above)
    // only specify width when it's not zero
    $htmlwidth = 'data-width="'.$width.'"';
    if ($width == "0") { $htmlwidth = ''; } 
    $html = "<div class=\"g-plusone\" data-href=\"{$link}\" " . 
        "data-size=\"{$size}\" " . 
        "data-annotation=\"{$annotation}\" {$htmlwidth} " . 
        "data-align=\"{$align}\" data-expandTo=\"{$bubble}\" " . 
        "></div>";
        
    return $html;
}
?>