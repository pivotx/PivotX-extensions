<?php
// - Extension: Image Tools
// - Version: 0.8.1
// - Author: PivotX Team
// - Email: admin@pivotx.net
// - Site: http://www.pivotx.net
// - Description: A collection of small tools to simplify handling images in your content.
// - Date: 2015-01-10
// - Identifier: imagetools
// - Required PivotX version: 2.3.6

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
        if ($params['noencode'] == true) {
            $imgparams[] = "src=".$params['src'];
        } else {
            $imgparams[] = "src=".base64_encode($params['src']);
        }
    }

    $whcombined = "";
    if (!empty($params['w'])) {
        $imgparams[] = "w=".$params['w'];
        $whcombined  = "width='".$params['w']."'";
    }

    if (!empty($params['h'])) {
        $imgparams[] = "h=".$params['h'];
        $whcombined  .= " height='".$params['h']."'";
    }
    // if not specified timthumb default will be used (see timthumb-config.php)
    if (!empty($params['zc'])) {
        $imgparams[] = "zc=".$params['zc'];
    }   
    // does this parm do anything? timthumb doesn't use it.........
    if (!empty($params['fit'])) {
        $imgparams[] = "fit=".$params['fit'];
    }

    $url = $url . implode("&amp;", $imgparams);

    // keep downward compatibility
    $title  = getDefault($params['alt'], $params['title']);
    $alt    = getDefault($params['alt'], "");
    // but if both are specified use title
    if (!empty($params['alt']) && !empty($params['title'])) {
        $title = $params['title'];
    }
    $target = getDefault($params['target'], "_blank");

    if (empty($title)) {
        $title = basename($params['src']);
    }

    if (!empty($params['class'])) {
        $class = " class='".$params['class'] ."'";
    } else {
        $class = "";
    }

    if (!empty($params['id'])) {
        $id = " id='".$params['id'] ."'";
    } else {
        $id = "";
    }
    $idsav = "";
    if (!empty($params['link'])) {
        $idsav = $id;
        $id    = "";
    }

    $img = sprintf("<img %s src=\"%s\" title=\"%s\" alt=\"%s\" %s %s />",
        $id,
        $url,
        htmlentities($title, ENT_QUOTES),
        htmlentities($alt, ENT_QUOTES),
        $whcombined,
        $class
    );

    if (!empty($params['link'])) {
        $id = $idsav;
        
        $linkmaxsize = getDefault($params['linkmaxsize'], 1000);
        $uplbasepath = $PIVOTX['paths']['upload_base_path'];
        list($srcw, $srch) = getimagesize($uplbasepath.$params['src']);
        // list won't work for images on other locations; continue linkmaxsize for such situations
        if (!$srcw || $srcw > $linkmaxsize) { $srcw = $linkmaxsize; }
        if (!$srch || $srch > $linkmaxsize) { $srch = $linkmaxsize; }
        
        if (empty($params['htmlwrap']) && empty($params['htmlwrapper'])) {
            $link = $PIVOTX['paths']['pivotx_url'] . "includes/timthumb.php?"; 
        } else {
            // timwrapper does not work with fancybox
            if ($params['linkclass'] == 'fancybox') {
                debug("Imagetools: fancybox and htmlwrap don't go together -- thickbox used instead");
                $params['linkclass'] = 'thickbox';
            }
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
        if ($params['noencode'] == true) {
            $linkparams[] = "src=" . $params['src'];
        } else {
            $linkparams[] = "src=" . base64_encode($params['src']);
        }
        $linkparams[] = "w=" . $srcw;
        $linkparams[] = "h=" . $srch;
        $linkparams[] = "fit=1";
        $linkparams[] = "type=." . getExtension($params['src']);

        $link = $link . implode("&amp;", $linkparams);
            
        $link = sprintf("<a %s href=\"%s\"%s%s title='%s' target='%s'>%s</a>", 
            $id, $link, $rel, $class, $title, $target, $img);
        
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
