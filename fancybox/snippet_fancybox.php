<?php
// - Extension: Fancybox
// - Version: 0.10
// - Author: PivotX Team / Harm Kramer
// - Email: admin@pivotx.net / harm.kramer@hccnet.nl
// - Site: http://www.pivotx.net
// - Description: Replace boring old Thickbox with a FancyBox!
// - Date: 2010-09-10
// - Identifier: fancybox 
// - Required PivotX version: 2.0.2


// Register 'fancybox' as a smarty tag, and override 'popup'
$PIVOTX['template']->register_function('fancybox', 'smarty_fancybox');
$PIVOTX['template']->unregister_function('popup');
$PIVOTX['template']->register_function('popup', 'smarty_fancybox');


/**
 * Outputs the Fancybox popup code.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_fancybox($params, &$smarty) {
    global $PIVOTX;

    // If we've set the hidden config option for 'never_jquery', just return without doing anything.
    if ($PIVOTX['config']->get('never_jquery') == 1) {
        debug("JQuery is disabled by the 'never_jquery' config option. FancyBox won't work.");
        return;   
    }

    $params = cleanParams($params);

    $filename      = $params['file'];
    $thumbname     = getDefault($params['description'], "(thumbnail)");
    $org_thumbname = $thumbname;
    $alt           = $params['alt'];
    $title         = $params['title'];
    $align         = getDefault($params['align'], "center");
    // rel_id can be used to specify your own prefix; all fancybox images with the same prefix will become a gallery
    $rel_id        = getDefault($params['rel_id'], "entry-");
    // fb_type can be used to specify the type of the fancybox
    // image (default) - selfexplanatory
    // youtube - creates an embedded object with the youtube link (use url for that)
    $fb_type       = getDefault($params['fb_type'], "image");
    $width         = getDefault($params['width'], "560");
    $height        = getDefault($params['height'], "340");
    $maxthumb      = getDefault($params['specthumbmax'], "0");
    // this one can be used together with fb_type="youtube" and "vimeo"
    // !! structure should be like explained on youtube e.g. http://www.youtube.com/v/MOVID
    // or for vimeo: http://www.vimeo.com/moogaloop.swf?clip_id=CLIPID
    // it's better to just use movid to specify youtube or clipid for vimeo
    // url can also be used for fb type="iframe" or "flash"
    $url           = $params['url'];
    $url = strip_tags($url);
    $movid         = $params['movid'];
    $text          = getDefault($params['text'], "Specify your text in parm 'text'.");
    // $border = getDefault($params['border'], 0);
    $uplw          = getDefault($PIVOTX['config']->get('upload_thumb_width'), 200);
    $uplh          = getDefault($PIVOTX['config']->get('upload_thumb_height'), 200);
    $uplpath       = $PIVOTX['config']->get('upload_path');

    // debug("fb info: '$filename'-'$thumbname'-'$title'-'$alt'-'$align'");

    if (($align=='center'||($align=='inline'))){
        $fbclass = 'pivotx-popupimage';
        $txclass = 'pivotx-popuptext';
    } else {
        $fbclass = 'pivotx-popupimage align-'.$align;
        $txclass = 'pivotx-popuptext align-'.$align;
    }

    // Get the UID for the page or entry
    $vars = $smarty->get_template_vars();
    $uid = intval($vars['uid']);

    if ( empty($alt) ) {
        $alt = $filename;
    }
    // Fix Thumbname, perhaps use a thumbname, instead of textual link
    // and try to fill both alt and title if still empty
    if ( $thumbname=="(thumbnail)" ) {
        if ( empty($filename) ) {
            debug ("No filename specified for thumbnail to process");          
        } else {
            $thumbname = makeThumbname($filename);
            // If the thumbnail does not exist and extension is jpg or png then try to create it
            // gif could be problematic so don't try it here......
            if( !file_exists( $PIVOTX['paths']['upload_base_path'].$thumbname )) {
                $ext = strtolower(getExtension($filename));
                if(($ext=="jpeg")||($ext=="jpg")||($ext=="png")) {
                    require_once($PIVOTX['paths']['pivotx_path'].'modules/module_imagefunctions.php');
                    if (!auto_thumbnail($filename)) {
                        debug("Failed to create thumbnail for " . $filename);
                    } 
                } else {
                    debug("Unable to create thumbnail for this extension " . $filename);
                }
            }    
        }   
    }
    if ( empty($alt) ) {
        $alt = $thumbname;
    }    
    if ( empty($title) ) {
        $title = $alt;
    }

    // If the thumbnail exists, make the HTML for it, else just use the text for a link.
    // use the current settings for uploadwidth/height because thumb can have diff.size
    if( file_exists( $PIVOTX['paths']['upload_base_path'].$thumbname )) {

        $ext=strtolower(getExtension($thumbname));

        if ( ($ext=="jpg")||($ext=="jpeg")||($ext=="gif")||($ext=="png") ) {
            if ($maxthumb > 0) {
               // get size of thumbimage and calculate the right values (useful for vertical images)
               list($thumbw, $thumbh) = getimagesize("../".$uplpath.$thumbname);
               if ($thumbw > $thumbh) {
                  $uplh = round($thumbh * ($maxthumb / $thumbw));
                  $uplw = $maxthumb;
               }   
               else {
                  $uplw = round($thumbw * ($maxthumb / $thumbh));
                  $uplh = $maxthumb;   
               }
            }
            // if parms width or height have been specified they should be used!
            if (isset($params['width'])) {
               $uplw = $width;
            }
            if (isset($params['height'])) {
               $uplh = $height;
            }
            $thumbname = sprintf("<img src=\"%s%s\" alt=\"%s\" title=\"%s\" class=\"%s\" width=\"%s\" height=\"%s\" />",
                $PIVOTX['paths']['upload_base_url'], $thumbname, $alt, $title, $fbclass, $uplw, $uplh
            );
        } else {
            $thumbname = $org_thumbname;
        }
    } else {
        $thumbname = $org_thumbname;
    }
    // pack text in aligned paragraph (thumbname has been unchanged by the above)
    if ($thumbname == $org_thumbname) {
        if (strlen($org_thumbname)<2) {
            $org_thumbname = "popup";
        }   
        $thumbname = sprintf("<span class=\"%s\">%s</span>",
            $txclass,
            $org_thumbname
        );
    }


    // Prepare the HMTL for the link to the popup..
    // fb_type image
    if ($fb_type=='image') {
        if( file_exists( $PIVOTX['paths']['upload_base_path'].$filename )) {

            $filename = $PIVOTX['paths']['upload_base_url'].$filename ;

            $code = sprintf( "<a href=\"%s\" class=\"fancybox\" title=\"%s\" rel=\"%s%s\" >%s</a>",
                $filename,
                $title,
                $rel_id,
                $uid,
                $thumbname
            );
            if( 'center'==$align ) {
                $code = '<p class="pivotx-wrapper">'.$code.'</p>' ;
            }
        } else {
            debug("Rendering error: could not popup '$filename'. File does not exist.");
            $code = "<!-- Rendering error: could not popup '$filename'. File does not exist. -->";
        }
    } else if (($fb_type=='youtube')||($fb_type=="vimeo")) {
        // use random number to be fairly sure that constructed href will be unique 
        // if by chance the number is the same then movie shown (when clicked) will be the first one
        // this is because a gallery of movies is not possible yet
        // uploadwidth/height is not used here because default youtube images are smaller
        $randnum = rand();
        if (empty($movid) && empty($url)) {
            debug("Popup type youtube/vimeo needs either a 'movid' or a fully qualified 'url' parm!");
        } 
        if (empty($movid)) {
            $movthumb = formatFilename($url);
            if ($fb_type=="vimeo"){
                // possible formats: http://www.vimeo.com/moogaloop.swf?clip_id=6566857 
                //                   http://www.vimeo.com/5324878
                $pos = strpos($url, "clip_id=");
                if ($pos !== false){
                    $pos = $pos + 8;
                    $movthumb = substr($url,$pos);
                } else {
                    $pos = strpos($url, "vimeo.com/");
                    if ($pos !== false){
                        $pos = $pos + 10;
                        $movthumb = substr($url,$pos);
                        // if this format is received rewrite it to embed format
                        $url = "http://www.vimeo.com/moogaloop.swf?clip_id=" . $movthumb;
                    }
                }
            }
        } else {
            $movthumb = $movid;
        }
        if ($fb_type=="youtube"){
            $urlthumb = "http://i2.ytimg.com/vi/" . $movthumb . "/default.jpg";
        } else if ($fb_type=="vimeo"){
            $urlvimphp = "http://vimeo.com/api/clip/" . $movthumb . "/php";
            $vimeocontents = @file_get_contents($urlvimphp);
            $thumbcontents = @unserialize(trim($vimeocontents));
            $urlthumb = $thumbcontents[0][thumbnail_small];
            if (empty($urlthumb)){
                $urlthumb = $thumbcontents[0][user_thumbnail_small];
            }
        }

        $code = sprintf( "<a href=\"#%s%s\" class=\"fancytube\" title=\"%s\" rel=\"%s%s\" ><img src=\"%s\" class=\"%s\" alt=\"%s\" /></a>",
            $rel_id,
            $randnum,
            $title,
            $rel_id,
            $uid,
            $urlthumb,
            $fbclass,
            $alt
        );
        
        // some extra options for youtube (end with ampersand)
        // for explanation see http://code.google.com/intl/nl/apis/youtube/player_parameters.html
        // hl = language
        // autoplay: 1 = autoplay; 0 = click to play
        // rel = play related videos (0 = no)
        // fs = fullscreen allowed

        // options for vimeo just found by browsing through Google
        if (empty($movid)) {
            $urlmain = $url;
            $urlid   = "";
        } else {
            if ($fb_type=="youtube"){
                $urlmain = "http://www.youtube.com/v/";
                $urlid   = $movid;
            } else if ($fb_type=="vimeo"){
                $urlmain = "http://www.vimeo.com/moogaloop.swf?clip_id=";
                $urlid   = $movid;            
            }   
        }
        if ($fb_type=="youtube"){
            $urlextra = "&amp;hl=en&amp;autoplay=1&amp;rel=0&amp;fs=1";
        } else if ($fb_type=="vimeo"){
            $urlextra = "&amp;server=vimeo.com&amp;autoplay=1&amp;fullscreen=1&amp;show_title=1&amp;show_byline=0&amp;show_portrait=0";
        }    
        $anchor_obj = sprintf( "<span style=\"display: none\"><span id=\"%s%s\" ><object type=\"application/x-shockwave-flash\" data=\"%s%s%s\" width=\"%s\" height=\"%s\"><param name=\"movie\" value=\"%s%s%s\"></param><param name=\"allowFullScreen\" value=\"true\"></param><param name=\"allowscriptaccess\" value=\"always\"></param></object></span></span>",
            $rel_id,
            $randnum,
            $urlmain,
            $urlid,
            $urlextra,
            $width,
            $height,
            $urlmain,
            $urlid,
            $urlextra            
        );
        $code = $code.$anchor_obj ;
        if( 'center'==$align ) {
            $code = '<p class="pivotx-wrapper">'.$code.'</p>' ;
        }
    }  else if ($fb_type=='text') {
        // use random number to be fairly sure that constructed href will be unique 
        // if by chance the number is the same then text shown (when clicked) will be the first one
        $randnum = rand();
        $code = sprintf( "<a href=\"#%s%s\" class=\"fancytext\" title=\"%s\" rel=\"%s%s\" >%s</a>",
            $rel_id,
            $randnum,
            $title,
            $rel_id,
            $uid,
            $thumbname
        );
        $textbegin = substr($text,0,5);
        $textrest  = substr($text,5);
        if ($textbegin !== "file:") {
            $lines = $text;
        } else {
            $docfile = $PIVOTX['paths']['pivotx_path']."docs/".$textrest ;
            if( file_exists($docfile) && is_readable($docfile) && ($handle = fopen($docfile, 'r'))) {
                $lines    = fread($handle, filesize($docfile));
                fclose($handle);
            } else {
                debug("Specified file cannot be found or read:'$docfile'");
            }
        }
        // check whether the lines contain main html elements. If they are there the popup will
        // still function but results in invalid html
        $texthtml = strpos($lines, '<html');
        $texthead = strpos($lines, '<head');
        $texttitle = strpos($lines, '<title');
        $textbody = strpos($lines, '<body');
        if (($texthtml!==false)||($texthead!==false)||($texttitle!==false)||($textbody!==false)) {
            debug("popup: '$rel_id$randnum' contains main html elements; a text popup should only contain plain html elements (like div or p)");
        }
        
        $anchor_obj = sprintf( "<span style=\"display: none\"><span id=\"%s%s\" style=\"width: %s; height: %s; color: #000000; overflow: auto\"><object type=\"text/html\" width=\"%s\" height=\"%s\">%s</object></span></span>",
            $rel_id,
            $randnum,
            $width,
            $height,
            $width,
            $height,
            $lines
        );
        $code = $code.$anchor_obj ;
        if( 'center'==$align ) {
            $code = '<p class="pivotx-wrapper">'.$code.'</p>' ;
        }
    } else if ($fb_type=='iframe') {
        // use random number to be fairly sure that constructed rel will be unique 
        // if by chance the number is the same then iframe will open but clicking 
        // in the frame itself will be impossible
        $randnum = rand();
        $code = sprintf( "<a href=\"%s\" class=\"fancyframe\" title=\"%s\" rel=\"%s%s%s\" >%s</a>",
            $url,
            $title,
            $rel_id,
            $uid,
            $randnum,
            $thumbname
        );
        if( 'center'==$align ) {
            $code = '<p class="pivotx-wrapper">'.$code.'</p>' ;
        }
    } else if ($fb_type=='flash') {
        // use random number to be fairly sure that constructed rel will be unique 
        // if by chance the number is the same then flash will open but clicking 
        // in the window itself will be impossible
        $randnum = rand();
        $code = sprintf( "<a href=\"%s\" class=\"fancyflash\" title=\"%s\" rel=\"%s%s%s\" >%s</a>",
            $url,
            $title,
            $rel_id,
            $uid,
            $randnum,
            $thumbname
        );
        if( 'center'==$align ) {
            $code = '<p class="pivotx-wrapper">'.$code.'</p>' ;
        }
    }  

    $PIVOTX['extensions']->addHook('after_parse', 'callback', 'fancyboxIncludeCallback');

    return $code;


}

/**
 * Try to insert the includes for fancybox in the <head> section of the HTML
 * that is to be outputted to the browser. Inserts Jquery if not already 
 * included. (This is just the default "thickboxIncludeCallback" function 
 * adapted to Fancybox.)
 *
 * @param string $html
 */
function fancyboxIncludeCallback(&$html) {
    global $PIVOTX;

    // If we've set the hidden config option for 'never_jquery', just return without doing anything.
    if ($PIVOTX['config']->get('never_jquery') == 1) {
        debug("JQuery is disabled by the 'never_jquery' config option. FancyBox won't work.");
        return;   
    }

    $jqueryincluded = false;
    $insert = '';

    if (!preg_match("#<script [^>]*?/jquery[a-z0-9_-]*\.js['\"][^>]*?>\s*</script>#i", $html)) {
        // We need to include Jquery
        $insert .= "\n\t<!-- Main JQuery include -->\n";
        $insert .= sprintf("\t<script type=\"text/javascript\" src=\"%sincludes/js/jquery.js\"></script>\n",
        $PIVOTX['paths']['pivotx_url'] );
        $jqueryincluded = true;
    }

    // Is config option 'fancybox_profile' added and has an expected value?
    $fbprof = $PIVOTX['config']->get('fancybox_profile');
    // default profile (downwards compatible)
    // for parms explanation -- see http://www.fancybox.net/api
    $fbparms = "\t jQuery(\"a.fancybox\").fancybox({ padding: 2, 'titlePosition': 'over', 'overlayShow': true, 'overlayOpacity': 0.25, 'opacity': true, 'speedIn': 100, 'speedOut': 100, 'changeSpeed': 100, 'showCloseButton': true });\n";
    if ($fbprof == '') {
        // profile will be default = nr. 1
    } elseif ($fbprof == 1) {
        // is the default
    } elseif ($fbprof == 2) {       
        $fbparms = "\t jQuery(\"a.fancybox\").fancybox({ ";
        // title outside / elastic transition / diff.speed / cyclic
        $fbparms .= "padding: 2, ";
        $fbparms .= "'titlePosition': 'outside', ";
        $fbparms .= "'transitionIn': 'elastic', 'transitionOut': 'elastic', ";
        $fbparms .= "'easingIn': 'easeOutBack', 'easingOut': 'easeInBack', "; 
        $fbparms .= "'overlayShow': true, 'overlayOpacity': 0.3, ";
        $fbparms .= "'opacity': true, 'speedIn': 300, 'speedOut': 300, 'changeSpeed': 300, ";
        $fbparms .= "'showCloseButton': true, 'cyclic': true ";
        $fbparms .= "});\n";		   
    } elseif ($fbprof == 3) {
        $fbparms = "\t jQuery(\"a.fancybox\").fancybox({ ";
        // no padding / no close button / Image 1/n before title (over) / cyclic
        $fbparms .= "padding: 0, ";
        $fbparms .= "'titlePosition': 'over', ";
        $fbparms .= "'transitionIn': 'fade', 'transitionOut': 'fade', ";
        $fbparms .= "'overlayShow': true, 'overlayOpacity': 0.25, ";
        $fbparms .= "'opacity': true, 'speedIn': 100, 'speedOut': 100, 'changeSpeed': 100, ";
        $fbparms .= "'showCloseButton': false, 'cyclic': true, ";
        $fbparms .= "'titleFormat': function(title, currentArray, currentIndex, currentOpts) { return '<span id=\"fancybox-title-over\">";
        $fbparms .= __("Image");
        $fbparms .= " ' + (currentIndex + 1) + ' / ' + currentArray.length + (title.length ? ' &nbsp; ' + title : '') + '</span>';}";
        $fbparms .= "});\n";
    } else {              
        debug("Config option fancybox_profile has an incorrect value, profile set to default.");   
    } 
    // standard fancybox youtube profile   
    $fbytube = "\t jQuery(\"a.fancytube\").fancybox({ ";
    $fbytube .= "padding: 0, autoScale: false, centerOnScroll: true, ";
    $fbytube .= "'transitionIn': 'none', 'transitionOut': 'none', ";
    $fbytube .= "'overlayShow': true, 'overlayOpacity': 0.7, "; 
    $fbytube .= "'hideOnContentClick': true, ";     // doesn't work for youtube (yet?)
    $fbytube .= "'titlePosition': 'outside', ";
    $fbytube .= "'showCloseButton': false ";
    $fbytube .= "});\n";	

    // standard fancybox text profile   
    $fbtext  = "\t jQuery(\"a.fancytext\").fancybox({ ";
    $fbtext .= "padding: 5, autoScale: true, centerOnScroll: true, ";
    $fbtext .= "'transitionIn': 'none', 'transitionOut': 'none', ";
    $fbtext .= "'overlayShow': true, 'overlayOpacity': 0.7, "; 
    $fbtext .= "'titlePosition': 'outside', ";
    $fbtext .= "'showCloseButton': true, 'cyclic': false ";
    $fbtext .= "});\n";	

    // standard fancybox iframe profile   
    $fbifram = "\t jQuery(\"a.fancyframe\").fancybox({ ";
    $fbifram .= "padding: 3, autoScale: false, centerOnScroll: true, ";
    $fbifram .= "'transitionIn': 'none', 'transitionOut': 'none', ";
    $fbifram .= "'overlayShow': true, 'overlayOpacity': 0.7, "; 
    $fbifram .= "'width': '75%', 'height': '75%', ";
    $fbifram .= "'type': 'iframe', ";
    $fbifram .= "'titlePosition': 'outside', ";
    $fbifram .= "'showCloseButton': true ";
    $fbifram .= "});\n";	

    // standard fancybox swf/flash profile   
    $fbflash = "\t jQuery(\"a.fancyflash\").fancybox({ ";
    $fbflash .= "padding: 0, autoScale: false, ";
    $fbflash .= "'transitionIn': 'none', 'transitionOut': 'none', ";
    $fbflash .= "'showCloseButton': true ";
    $fbflash .= "});\n";	

    $path = $PIVOTX['paths']['extensions_url']."fancybox/";

    $insert .= "\n\t<!-- Includes for Fancybox script -->\n";
    $insert .= "\t<link rel=\"stylesheet\" href=\"{$path}jquery.fancybox-1.3.1.css\" type=\"text/css\" media=\"screen\" />\n";
    $insert .= "\t<!--[if IE]>\n";
    $insert .= "\t<link rel=\"stylesheet\" href=\"{$path}jquery.fancybox_IE_-1.3.1.css\" type=\"text/css\" media=\"screen\" />\n";
    $insert .= "\t<![endif]-->\n";

    // easing only needed for elastic transition
    if ($fbprof == 2){
        $insert .= "\t<script type=\"text/javascript\" src=\"{$path}jquery.easing-1.3.js\"></script>\n";
    }
    // only add mousewheel when fancybox_profile has been set to something
    if (!$fbprof == ''){
        $insert .= "\t<script type=\"text/javascript\" src=\"{$path}jquery.mousewheel-3.0.2.js\"></script>\n";
    }
    $insert .= "\t<script type=\"text/javascript\" src=\"{$path}jquery.fancybox-1.3.1.js\"></script>\n";
    $insert .= "\t<script type=\"text/javascript\">\n";
    // insert html comment within this script to fool markup validation
    $insert .= "\t<!--\n";
    $insert .= "\t\tjQuery.noConflict();\n";
    $insert .= "\t\tjQuery(document).ready(function() {\n";
    $insert .= $fbparms;
    $insert .= $fbytube;
    $insert .= $fbtext;
    $insert .= $fbifram;
    $insert .= $fbflash;
    $insert .= "\t});\n";    
    // insert html comment within this script to fool markup validation
    $insert .= "\t// -->\n";
    $insert .= "\t</script>\n";    
    
    // If JQuery was added earlier, we must insert the FB code after that. Else we 
    // insert the code after the meta tag for the charset (since it ought to be first
    // in the header) or if no charset meta tag we insert it at the top of the head section.
    if (!$jqueryincluded) {
        $html = preg_replace("#<script ([^>]*?/jquery[a-z0-9_-]*\.js['\"][^>]*?)>\s*</script>#si", 
            "<script $1></script>\n" . $insert, $html, 1);
    } elseif (preg_match("/<meta http-equiv=['\"]Content-Type/si", $html)) {
        $html = preg_replace("/<meta http-equiv=(['\"]Content-Type[^>]*?)>/si", "<meta http-equiv=$1>\n" . $insert, $html);
    } else {
        $html = preg_replace("/<head([^>]*?)>/si", "<head$1>\n" . $insert, $html);
    }

}



?>
