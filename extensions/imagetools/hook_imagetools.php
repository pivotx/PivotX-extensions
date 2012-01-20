<?php
// - Extension: Image Tools
// - Version: 0.7
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: A collection of small tools to simplify handling images in your content.
// - Date: 2012-01-20
// - Identifier: imagetools
// - Required PivotX version: 2.2.4

// Register 'findimages' as a smarty tag.
$PIVOTX['template']->register_function('findimages', 'smarty_findimages');

function smarty_findimages($params, &$smarty) {
    global $PIVOTX;

    $vars = $smarty->get_template_vars();
    $entry = $vars['entry'];
    $page = $vars['page'];

    $var = getDefault($params['var'], "imagelist");

    // Get the images from the Entry or whatever is in 'var'..
    if (!empty($params['source'])) {
        $html = implodeDeep("\n", $params['source']);
    } else if (!empty($entry)) {
        $html = implodeDeep("\n", $entry);
    } else {
        $html = implodeDeep("\n", $page);        
    }
    
    preg_match_all('/[a-z0-9\\.\+\ %:_\/-]+.(jpg|jpeg|gif|png)/i', $html, $match);

    $smarty->assign($var, $match[0]);

}
    
    
// Register 'thumbnail' as a smarty tag.
$PIVOTX['template']->register_function('thumbnail', 'smarty_thumbnail');

function smarty_thumbnail($params, &$smarty) {
    global $PIVOTX;

    $url = $PIVOTX['paths']['pivotx_url'] . "includes/timthumb.php?";
    $imgparams = array();

    if (!empty($params['src'])) {
        $imgparams[] = "src=".base64_encode($params['src']);
    }

    if (!empty($params['w'])) {
        $imgparams[] = "w=".$params['w'];
    }

    if (!empty($params['h'])) {
        $imgparams[] = "h=".$params['h'];
    }
    // if not specified timthumb default will be used (or when it is overriden by timthumb_zc setting)
    if (!empty($params['zc'])) {
        $imgparams[] = "zc=".$params['zc'];
    }   
    // does this parm do anything? timthumb doesn't use it.........
    if (!empty($params['fit'])) {
        $imgparams[] = "fit=".$params['fit'];
    }

    $url = $url . implode("&amp;", $imgparams);

    $title = getDefault($params['alt'], $params['title']);
    $target = getDefault($params['target'], "_blank");

    if (empty($title)) {
        $title = basename($url);
    }

    if (!empty($params['class'])) {
        $class = " class='".$params['class'] ."'";
    } else {
        $class = "";
    }

    $img = sprintf("<img src=\"%s\" alt=\"%s\" width=\"%s\" height=\"%s\"%s />",
        $url,
        htmlentities($title, ENT_QUOTES),
        $params['w'],
        $params['h'],
        $class
    );


    if (!empty($params['link'])) {
        
        $linkmaxsize = getDefault($params['linkmaxsize'], 1000);
        $uplbasepath = $PIVOTX['paths']['upload_base_path'];
        list($srcw, $srch) = getimagesize($uplbasepath.$params['src']);
        // list won't work for images on other locations; continue linkmaxsize for such situations
        if (!$srcw || $srcw > $linkmaxsize) { $srcw = $linkmaxsize; }
        if (!$srch || $srch > $linkmaxsize) { $srch = $linkmaxsize; }
        
        if (empty($params['htmlwrap']) && empty($params['htmlwrapper'])) {
            $link = $PIVOTX['paths']['pivotx_url'] . "includes/timthumb.php?"; 
        } else {
            $link = $PIVOTX['paths']['pivotx_url'] . "includes/timwrapper.php?";
        }
        
        if (!empty($params['linkclass'])) {
            $class = " class='".$params['linkclass'] ."'";
        } else {
            $class = "";
        }
        
        if (!empty($params['rel'])) {
            $rel = " rel='" . htmlentities($params['rel'], ENT_QUOTES) . "'";
        } else {
            $rel = "";
        }   
        
        $linkparams = array();
        $linkparams[] = "src=" . base64_encode($params['src']);
        $linkparams[] = "w=" . $srcw;
        $linkparams[] = "h=" . $srch;
        $linkparams[] = "fit=1";
        $linkparams[] = "type=." . getExtension($params['src']);

        $link = $link . implode("&amp;", $linkparams);
            
        $link = sprintf("<a href=\"%s\"%s%s title='%s' target='%s'>%s</a>", 
            $link, $rel, $class, $title, $target, $img);
        
        return $link;
        
    } else {
        
        return $img;
    
    }

}


// Register 'stripimages' as a smarty modifier.
$PIVOTX['template']->register_modifier('stripimages', 'smarty_stripimages');

function smarty_stripimages($html) {
    global $PIVOTX;

    $html = stripOnlyTags($html, "<img>");
    
    return $html;

}


?>
